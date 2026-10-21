<?php

use App\Controllers\FacturasController;
use PHPUnit\Framework\TestCase;

final class InvalidacionReemplazoTest extends TestCase
{
    public function testRescindirOmitsReplacementEvenIfTheFormSendsOne(): void
    {
        $evento = $this->generarEvento('2', '12345678-1234-4234-8234-123456789ABC');
        self::assertNull($evento['documento']['codigoGeneracionR']);
        self::assertSame(2, $evento['motivo']['tipoAnulacion']);
        self::assertStringContainsString('"codigoGeneracionR":null', json_encode($evento));
    }

    public function testOtherReasonsPreserveReplacement(): void
    {
        $codigo = '12345678-1234-4234-8234-123456789ABC';
        foreach (['1', '3'] as $tipo) {
            self::assertSame($codigo, $this->generarEvento($tipo, $codigo)['documento']['codigoGeneracionR']);
        }
    }

    public function testCreditFiscalAndRelatedDocumentsUseOriginalDateForEvent(): void
    {
        foreach (['03', '04', '05', '06', '07', '08', '09', '15'] as $tipoDte) {
            $evento = $this->generarEvento('2', '', $tipoDte);
            self::assertSame('2026-10-20', $evento['identificacion']['fecEmi'], $tipoDte);
            self::assertSame('2026-10-20', $evento['documento']['fecEmi'], $tipoDte);
        }
    }

    public function testInvoiceExportAndExcludedSubjectKeepCurrentEventDate(): void
    {
        $hoy = (new DateTimeImmutable('now', new DateTimeZone('America/El_Salvador')))->format('Y-m-d');
        foreach (['01', '11', '14'] as $tipoDte) {
            $evento = $this->generarEvento('2', '', $tipoDte);
            self::assertSame($hoy, $evento['identificacion']['fecEmi'], $tipoDte);
            self::assertSame('2026-10-20', $evento['documento']['fecEmi'], $tipoDte);
        }
    }

    private function generarEvento(string $tipo, string $reemplazo, string $tipoDte = '03'): array
    {
        $reflection = new ReflectionClass(FacturasController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('generarInvalidacion');
        $method->setAccessible(true);
        $motivo = array_fill_keys([
            'motivoAnulacion', 'nombreResponsable', 'tipDocResponsable',
            'numDocResponsable', 'nombreSolicita', 'tipDocSolicita', 'numDocSolicita',
        ], 'Prueba');
        $motivo['tipoAnulacion'] = $tipo;
        $motivo['codigoGeneracionR'] = $reemplazo;
        $dte = array_fill_keys([
            'nitEmisor', 'nombreEmisor', 'codEstableEmisor', 'codPuntoVentaEmisor',
            'telefonoEmisor', 'correoEmisor', 'tipoDte', 'codigoGeneracion',
            'selloRecibido', 'numeroControl', 'fechaEmision', 'tipoDocumentoReceptor',
            'numDocumentoReceptor', 'nombreReceptor', 'telefonoReceptor', 'correoReceptor',
        ], 'Prueba');
        $dte['tipoDte'] = $tipoDte;
        $dte['fechaEmision'] = '2026-10-20';
        return $method->invoke($controller, $motivo, $dte, ['ambiente' => '00']);
    }
}
