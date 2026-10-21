<?php

// Ejecuta los generadores reales con un capturador de PDF, sin BD, ERP ni SMTP.
namespace App\Controllers {
    class BaseController {}
}

namespace {
    class TCPDF
    {
        public static array $cells = [];
        public static string $html = '';
        public function __construct(...$args) { self::$cells = []; self::$html = ''; }
        public function Cell(...$args): void { self::$cells[] = $args[2] ?? ''; }
        public function writeHTML($html, ...$args): void { self::$html .= $html; }
        public function __call($name, $args) { return 10; }
    }

    foreach (['PDF_PAGE_ORIENTATION' => 'P', 'PDF_UNIT' => 'mm', 'PDF_PAGE_FORMAT' => 'A4',
        'PDF_CREATOR' => 'test', 'PDF_MARGIN_LEFT' => 2, 'PDF_MARGIN_RIGHT' => 2,
        'FCPATH' => dirname(__DIR__, 2) . '/public/',
        'WRITEPATH' => sys_get_temp_dir() . '/'] as $name => $value) {
        define($name, $value);
    }
    require dirname(__DIR__, 2) . '/app/Controllers/FacturasController.php';
    class PdfController extends \App\Controllers\FacturasController
    {
        public function generarCodigoQR($data) { return (object) ['urlQr' => 'test.png', 'url' => 'test']; }
    }
    set_error_handler(static function ($severity, $message, $file, $line) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });
    $reflection = new \ReflectionClass(PdfController::class);
    $controller = $reflection->newInstanceWithoutConstructor();
    $maintenance = new class {
        public function obtenerSucursalPuntoVentaTipoEstablecimiento($code) {
            return [['sucursal' => 'Prueba', 'puntoVenta' => 'Prueba']];
        }
        public function obtenercondicionOperacion($code) { return [['condicionOperacion' => 'Contado']]; }
        public function obtenerUnidad($code) { return [['Unidad' => 'Unidad']]; }
    };
    (new \ReflectionProperty(\App\Controllers\FacturasController::class, 'modelMantenimientos'))->setValue($controller, $maintenance);
    $cases = [
        ['actual', ['ivaRete' => 1.25, 'ivaPerci' => 2.50], '1.25', '2.50'],
        ['anterior', ['ivaRete1' => 3.75, 'ivaPerci1' => 4.50], '3.75', '4.50'],
        ['ausente', [], '0.00', '0.00'],
        ['nulo', ['ivaRete' => null, 'ivaPerci' => null], '0.00', '0.00'],
        ['cero', ['ivaRete' => 0, 'ivaRete1' => 99, 'ivaPerci' => 0, 'ivaPerci1' => 99], '0.00', '0.00'],
    ];
    foreach (['01' => 'generarFactura', '03' => 'generarCreditoFiscal'] as $type => $method) {
        foreach ($cases as [$name, $fields, $retained, $perceived]) {
            $dte = json_decode(file_get_contents(dirname(__DIR__) . '/_support/fixtures/dte/' . $type . '.json'));
            unset($dte->resumen->ivaRete, $dte->resumen->ivaRete1, $dte->resumen->ivaPerci, $dte->resumen->ivaPerci1);
            foreach ($fields as $field => $value) { $dte->resumen->$field = $value; }
            // El IVA total nunca debe usarse como retención.
            $dte->resumen->totalIva = 13;
            $original = json_encode($dte);
            $pdfMethod = new \ReflectionMethod(\App\Controllers\FacturasController::class, $method);
            $pdfMethod->invoke($controller, $dte, (object) ['selloRecibido' => 'PRUEBA', 'fhProcesamiento' => '2026-10-21'], true, 'Prueba');
            $labels = ['IVA Retenido:' => $retained];
            if ($type === '03') { $labels['IVA Percibido:'] = $perceived; }
            foreach ($labels as $label => $expected) {
                $index = array_search($label, TCPDF::$cells, true);
                if ($index === false || TCPDF::$cells[$index + 1] !== $expected) {
                    throw new \RuntimeException("$type / $name: importe incorrecto para $label");
                }
            }
            if (json_encode($dte) !== $original) { throw new \RuntimeException('Se modificó el DTE original'); }
            echo "$type / $name: correcto\n";
        }
    }
    require dirname(__DIR__, 2) . '/app/Libraries/NotaPdf.php';
    foreach (['05', '06'] as $type) {
        foreach ($cases as [$name, $fields, $retained, $perceived]) {
            $dte = json_decode(file_get_contents(dirname(__DIR__) . '/_support/fixtures/dte/' . $type . '.json'));
            unset($dte->resumen->ivaRete, $dte->resumen->ivaRete1, $dte->resumen->ivaPerci, $dte->resumen->ivaPerci1);
            foreach ($fields as $field => $value) { $dte->resumen->$field = $value; }
            $original = json_encode($dte);
            (new \App\Libraries\NotaPdf())->generar($dte, null);
            foreach (['IVA Retenido' => $retained, 'IVA Percibido' => $perceived] as $label => $expected) {
                if (!preg_match('~' . $label . '</b></td><td[^>]*>' . preg_quote($expected, '~') . '</td>~', TCPDF::$html)) {
                    throw new \RuntimeException("$type / $name: importe incorrecto para $label");
                }
            }
            if (json_encode($dte) !== $original) { throw new \RuntimeException('Se modificó el DTE original'); }
            echo "$type / $name: correcto\n";
        }
    }
    restore_error_handler();
}
