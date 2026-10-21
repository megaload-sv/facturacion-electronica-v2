<?php

use App\Libraries\JsonValidator;
use PHPUnit\Framework\TestCase;

final class JsonValidatorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('WRITEPATH')) {
            define('WRITEPATH', dirname(__DIR__, 2) . '/writable/');
        }
    }

    public function testLoadsEachDocumentSchema(): void
    {
        foreach (['01', '03', '05', '06', '11', '14'] as $tipo) {
            $result = (new JsonValidator())->validateJson($tipo, new stdClass());
            self::assertFalse($result['valid'], $tipo);
            self::assertStringContainsString('identificacion', implode('\n', $result['errors']), $tipo);
        }
    }

    public function testRejectsUnsupportedType(): void
    {
        $result = (new JsonValidator())->validateJson('../fe-fc-v1', new stdClass());
        self::assertFalse($result['valid']);
        self::assertStringContainsString('Tipo de documento no soportado', $result['errors'][0]);
    }

    public function testEnforcesCurrentSchemaVersion(): void
    {
        // La versión se exige tal como la define el esquema actualizado.
        $documento = $this->document('01');
        $validator = new JsonValidator();
        $result = $validator->validateJson('01', $documento);
        self::assertTrue($result['valid']);
        self::assertStringNotContainsString('[data.identificacion.tipoModelo]', implode('\n', $result['errors']));

        $documento->identificacion->version = 1;
        $result = $validator->validateJson('01', $documento);
        self::assertFalse($result['valid']);
        self::assertContains('[data.identificacion.version] El valor debe ser 2.', $result['errors']);
    }

    public function testRejectsStringVersionWithoutMutatingDocument(): void
    {
        $documento = (object) ['identificacion' => (object) ['tipoDte' => '01', 'version' => '1']];
        $before = serialize($documento);
        $result = (new JsonValidator())->validateJson('01', $documento);
        self::assertFalse($result['valid']);
        self::assertContains('[data.identificacion.version] El tipo de dato no es válido; se esperaba: entero.', $result['errors']);
        self::assertSame($before, serialize($documento));
    }

    public function testAcceptsCompleteExamplesForAllSupportedTypes(): void
    {
        $validator = new JsonValidator();
        foreach (['01', '03', '05', '06', '11', '14'] as $tipo) {
            $documento = $this->document($tipo);
            $before = serialize($documento);
            $result = $validator->validateJson($tipo, $documento);
            self::assertTrue($result['valid'], $tipo . ': ' . implode('\n', $result['detalle']));
            self::assertFalse($result['error']);
            self::assertSame([], $result['detalle']);
            self::assertSame($before, serialize($documento));
            self::assertFalse($validator->validateErpResponse($this->erpResponse($documento))['error'], $tipo);
        }
    }

    public function testNotesNowRequireTotalPagar(): void
    {
        $validator = new JsonValidator();
        foreach (['05', '06'] as $tipo) {
            $documento = $this->document($tipo);
            self::assertTrue(property_exists($documento->resumen, 'totalPagar'));
            self::assertTrue($validator->validateJson($tipo, $documento)['valid']);
            unset($documento->resumen->totalPagar);
            $result = $validator->validateJson($tipo, $documento);
            self::assertFalse($result['valid']);
            self::assertContains('[data.resumen.totalPagar] El campo es requerido (debe estar presente).', $result['detalle']);
        }
    }

    public function testOtherTypesStillRequireTotalPagar(): void
    {
        foreach (['01', '03', '11', '14'] as $tipo) {
            $documento = $this->document($tipo);
            unset($documento->resumen->totalPagar);
            $result = (new JsonValidator())->validateJson($tipo, $documento);
            self::assertFalse($result['valid']);
            self::assertContains('[data.resumen.totalPagar] El campo es requerido (debe estar presente).', $result['detalle']);
        }
    }

    public function testNestedItemPathsAndEmailMessages(): void
    {
        $documento = $this->document('05');
        $documento->cuerpoDocumento[] = clone $documento->cuerpoDocumento[0];
        unset($documento->cuerpoDocumento[1]->cantidad);
        $documento->receptor->correo = 123;
        $result = (new JsonValidator())->validateJson('05', $documento);
        self::assertContains('[data.cuerpoDocumento[1].cantidad] El campo es requerido (debe estar presente).', $result['detalle']);
        self::assertContains('[data.receptor.correo] El tipo de dato no es válido; se esperaba: texto, null.', $result['detalle']);
    }

    public function testRequiredNullableFieldMayBeNullButCannotBeAbsent(): void
    {
        $documento = $this->document('05');
        self::assertNull($documento->receptor->nombreComercial);
        unset($documento->receptor->nombreComercial);
        $result = (new JsonValidator())->validateJson('05', $documento);
        self::assertContains('[data.receptor.nombreComercial] El campo es requerido (debe estar presente).', $result['detalle']);
    }

    public function testRecipientStructureComesFromEachSchema(): void
    {
        $validator = new JsonValidator();
        self::assertSame('receptor', $validator->getRecipientNode('14'));
        self::assertSame('receptor', $validator->getRecipientNode('11'));
        $documento = $this->document('14');
        $documento->sujetoExcluido = $documento->receptor;
        unset($documento->receptor);
        $result = $validator->validateJson('14', $documento);
        self::assertContains('[data.receptor] El campo es requerido (debe estar presente).', $result['detalle']);
        self::assertContains('[data.sujetoExcluido] La propiedad no está permitida para este tipo de documento.', $result['detalle']);
    }

    public function testEmisorPreparationDoesNotInjectUnsupportedFields(): void
    {
        $validator = new JsonValidator();
        foreach (['01', '03', '05', '06', '11', '14'] as $tipo) {
            $doc = $this->document($tipo);
            $validator->completeEmisor($tipo, $doc->emisor, [
                'nombreComercial' => 'Pruebas', 'tipoEstablecimiento' => '01',
                'codEstableMH' => 'M001', 'codPuntoVenta' => 'P001',
            ]);
            self::assertSame(!in_array($tipo, ['05', '06'], true), property_exists($doc->emisor, 'codPuntoVenta'));
            self::assertSame($tipo !== '14', property_exists($doc->emisor, 'nombreComercial'));
            $result = $validator->validateJson($tipo, $doc);
            self::assertTrue($result['valid'], implode('\n', $result['detalle']));
        }
    }

    public function testErpValidationDoesNotDuplicateDocumentRules(): void
    {
        $doc = $this->document('05');
        unset($doc->emisor, $doc->receptor, $doc->resumen, $doc->cuerpoDocumento);
        $response = $this->erpResponse($doc);
        $response->datainfo->codigoTipoDTE = 5;
        $validator = new JsonValidator();
        self::assertTrue($validator->validateErpResponse($response)['valid']);
        self::assertFalse($validator->validateJson('05', $doc)['valid']);
    }

    public function testErpRejectsMalformedEnvelopeAndInconsistentMetadata(): void
    {
        $validator = new JsonValidator();
        foreach ([null, [], 'texto', (object) ['error' => true]] as $response) {
            self::assertTrue($validator->validateErpResponse($response)['error']);
        }
        $response = $this->erpResponse($this->document('05'));
        $response->datainfo->codigoTipoDTE = '03';
        $response->datainfo->version = 1;
        $response->datainfo->codigoGeneracion = 'otro';
        $result = $validator->validateErpResponse($response);
        self::assertCount(3, $result['detalle']);
        $response = $this->erpResponse($this->document('05'));
        unset($response->datainfo->codigoPuntoVenta);
        self::assertFalse($validator->validateErpResponse($response)['valid']);
    }

    public function testGroupedErrorsKeepAllMissingAndAdditionalFieldPaths(): void
    {
        $doc = $this->document('05');
        unset($doc->receptor->tipoDocumento, $doc->receptor->nrc);
        $doc->resumen->subTotal = 1;
        $doc->resumen->extra = true;
        $result = (new JsonValidator())->validateJson('05', $doc);
        foreach (['tipoDocumento', 'nrc'] as $field) {
            self::assertContains("[data.receptor.{$field}] El campo es requerido (debe estar presente).", $result['detalle']);
        }
        foreach (['subTotal', 'extra'] as $field) {
            self::assertContains("[data.resumen.{$field}] La propiedad no está permitida para este tipo de documento.", $result['detalle']);
        }
        self::assertNotContains('[data.receptor] La propiedad no está permitida para este tipo de documento.', $result['detalle']);
        self::assertSame($result['errors'], $result['detalle']);
        self::assertTrue($result['error']);
        self::assertFalse($result['valid']);
    }

    public function testEnumAndNumericMessagesContainSchemaValues(): void
    {
        $doc = $this->document('05');
        $doc->identificacion->ambiente = '99';
        $doc->cuerpoDocumento[0]->cantidad = 0;
        $result = (new JsonValidator())->validateJson('05', $doc);
        self::assertContains('[data.identificacion.ambiente] El valor debe pertenecer a los valores permitidos: ["00","01"].', $result['detalle']);
        self::assertContains('[data.cuerpoDocumento[0].cantidad] El número debe ser mayor que 0.', $result['detalle']);
    }

    public function testDecimalPrecisionAndRepeatedValidationDoNotChangeData(): void
    {
        $validator = new JsonValidator();
        $doc = $this->document('05');
        $doc->resumen->montoTotalOperacion = 123.45;
        $before = serialize($doc);
        self::assertTrue($validator->validateJson('05', $doc)['valid']);
        self::assertSame($before, serialize($doc));
        $doc->resumen->montoTotalOperacion = 123.456;
        $result = $validator->validateJson('05', $doc);
        self::assertContains('[data.resumen.montoTotalOperacion] El número debe ser múltiplo de 0.01.', $result['detalle']);
        $doc->resumen->montoTotalOperacion = 123.45;
        self::assertTrue($validator->validateJson('05', $doc)['valid']);
    }

    public function testSummaryDecimalsDoNotFailBecauseOfFloatRepresentation(): void
    {
        $doc = $this->document('05');
        foreach (['totalGravada', 'subTotalVentas', 'montoTotalOperacion'] as $field) {
            $doc->resumen->$field = 870.1;
        }
        $doc->resumen->tributos = [(object) [
            'codigo' => '20',
            'descripcion' => 'Impuesto al Valor Agregado 13%',
            'valor' => 100.1,
        ]];

        $result = (new JsonValidator())->validateJson('05', $doc);
        self::assertTrue($result['valid'], implode('\n', $result['detalle']));
    }

    public function testMultipleOfStillRejectsAdditionalDecimals(): void
    {
        $validator = new JsonValidator();
        $doc = $this->document('05');
        $doc->resumen->montoTotalOperacion = 870.101;
        self::assertContains(
            '[data.resumen.montoTotalOperacion] El número debe ser múltiplo de 0.01.',
            $validator->validateJson('05', $doc)['detalle']
        );

        $doc = $this->document('05');
        $doc->cuerpoDocumento[0]->cantidad = 1.123456789;
        self::assertContains(
            '[data.cuerpoDocumento[0].cantidad] El número debe ser múltiplo de 1.0e-8.',
            $validator->validateJson('05', $doc)['detalle']
        );
    }

    public function testUsesCatalogV12ForDepartmentMunicipalityPairs(): void
    {
        $doc = $this->document('05');
        $doc->emisor->direccion->departamento = '05';
        $doc->emisor->direccion->municipio = '25';
        $doc->receptor->direccion->departamento = '06';
        $doc->receptor->direccion->municipio = '23';

        $result = (new JsonValidator())->validateJson('05', $doc);
        self::assertTrue($result['valid'], implode('\n', $result['detalle']));
    }

    public function testRequiresDistrictSeparatelyFromMunicipality(): void
    {
        $doc = $this->document('05');
        $doc->emisor->direccion->departamento = '05';
        $doc->emisor->direccion->municipio = '01';
        unset($doc->emisor->direccion->distrito);
        $result = (new JsonValidator())->validateJson('05', $doc);

        self::assertFalse($result['valid']);
        self::assertContains(
            '[data.emisor.direccion.distrito] El campo es requerido (debe estar presente).',
            $result['detalle']
        );
    }

    public function testAcceptsEveryDepartmentMunicipalityPairFromCatalogV12(): void
    {
        $catalog = [
            '01' => ['13', '14', '15'],
            '02' => ['14', '15', '16', '17'],
            '03' => ['17', '18', '19', '20'],
            '04' => ['34', '35', '36'],
            '05' => ['23', '24', '25', '26', '27', '28'],
            '06' => ['20', '21', '22', '23', '24'],
            '07' => ['17', '18'],
            '08' => ['23', '24', '25'],
            '09' => ['10', '11'],
            '10' => ['14', '15'],
            '11' => ['24', '25', '26'],
            '12' => ['21', '22', '23'],
            '13' => ['27', '28'],
            '14' => ['19', '20'],
        ];

        $validator = new JsonValidator();
        $checked = 0;
        foreach ($catalog as $departamento => $municipios) {
            $departamento = str_pad((string) $departamento, 2, '0', STR_PAD_LEFT);
            foreach ($municipios as $municipio) {
                $doc = $this->document('05');
                $doc->emisor->direccion->departamento = $departamento;
                $doc->emisor->direccion->municipio = $municipio;
                $result = $validator->validateJson('05', $doc);
                self::assertTrue($result['valid'], "Pareja {$departamento}/{$municipio} rechazada: " . implode('\n', $result['detalle']));
                $checked++;
            }
        }
        self::assertSame(44, $checked);
    }

    public function testAllIssuerAddressesRequireDistrictWithoutChangingMunicipality(): void
    {
        $validator = new JsonValidator();
        foreach (['01', '03', '05', '06', '11', '14'] as $tipo) {
            $doc = $this->document($tipo);
            $doc->emisor->direccion->departamento = '05';
            $doc->emisor->direccion->municipio = '24';
            $doc->emisor->direccion->distrito = '02';
            $before = serialize($doc);
            self::assertTrue($validator->validateJson($tipo, $doc)['valid'], $tipo);
            self::assertSame($before, serialize($doc));
            unset($doc->emisor->direccion->distrito);
            self::assertContains('[data.emisor.direccion.distrito] El campo es requerido (debe estar presente).', $validator->validateJson($tipo, $doc)['detalle']);
        }
    }

    public function testIssuerPreparationRemovesLegacyFieldsAndPreservesExportData(): void
    {
        $validator = new JsonValidator();
        foreach (['01', '03', '05', '06', '11', '14'] as $tipo) {
            $doc = $this->document($tipo);
            $doc->emisor->tipoEstablecimiento = '02';
            $doc->emisor->codEstableMH = 'M001';
            $doc->emisor->codPuntoVentaMH = 'P001';
            $doc->emisor->nombreComercial = 'Pruebas';
            $doc->emisor->codEstable = 'S001';
            $doc->emisor->codPuntoVenta = 'P001';
            $validator->completeEmisor($tipo, $doc->emisor, []);
            self::assertTrue($validator->validateJson($tipo, $doc)['valid'], $tipo);
            if ($tipo === '11') {
                self::assertSame(1, $doc->emisor->tipoItemExpor);
                unset($doc->emisor->tipoRegimen);
                self::assertContains('[data.emisor.tipoRegimen] El campo es requerido (debe estar presente).', $validator->validateJson($tipo, $doc)['detalle']);
            }
        }
    }

    public function testInvalidationCannotEnterInvoiceProcessing(): void
    {
        $response = $this->erpResponse($this->document('01'));
        $response->data->identificacion->tipoDte = 'invalidacion';
        self::assertFalse((new JsonValidator())->validateErpResponse($response)['valid']);
    }

    private function document(string $tipo): stdClass
    {
        return json_decode(file_get_contents(__DIR__ . '/../_support/fixtures/dte/' . $tipo . '.json'), false, 512, JSON_THROW_ON_ERROR);
    }

    private function erpResponse(stdClass $doc): stdClass
    {
        return (object) [
            'error' => false,
            'datainfo' => (object) [
                'identicadorNumInterno' => 1, 'correlativoFactCRM' => 'PRUEBA-1',
                'codigoGeneracion' => $doc->identificacion->codigoGeneracion,
                'codigoTipoDTE' => $doc->identificacion->tipoDte,
                'version' => $doc->identificacion->version,
                'fechaFactura' => '2026-09-03', 'codigoPuntoVenta' => 'P001', 'codPais' => 'SV',
            ],
            'data' => $doc,
        ];
    }
}
