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

    private function generarEvento(string $tipo, string $reemplazo): array
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
        return $method->invoke($controller, $motivo, $dte, ['ambiente' => '00']);
    }
}
