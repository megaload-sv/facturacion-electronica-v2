<?php

use App\Models\SellosdteModel;
use PHPUnit\Framework\TestCase;

final class ArchivoAnualTest extends TestCase
{
    public function testArchivoSeparaAniosYConservaAnulados(): void
    {
        $db = \Config\Database::connect([
            'DBDriver' => 'SQLite3', 'database' => ':memory:', 'DBPrefix' => '',
        ], false);
        $db->initialize();
        $db->connID->createFunction('year', static fn ($date) => (int) substr($date ?? '', 0, 4));
        $db->connID->createFunction('concat', static fn (...$values) => implode('', $values));
        $db->connID->createFunction('json_unquote', static fn ($value) => $value);
        $db->connID->createFunction('json_extract', static fn ($json, $path) => 'Receptor');
        $db->query('CREATE TABLE sellosdte (identicadorNumInterno TEXT, correlativoFactCRM TEXT,
            codigoGeneracion TEXT, codigoTipoDTE TEXT, codigoPuntoVenta TEXT,
            jsonDTE TEXT, numeroControlMH TEXT, fechaFactura TEXT, idEstadoDTE INTEGER,
            correoEnviado INTEGER, fechaCorreoEnviado TEXT, errorCorreo TEXT)');
        $db->query('CREATE TABLE cattipodocumento (codigo TEXT, valores TEXT)');
        $db->query('CREATE TABLE puntosdeventa (codigoPuntoVenta TEXT, idSucursal INTEGER, nombre TEXT)');
        $db->query('CREATE TABLE sucursales (idSucursal INTEGER, codigoEmpresa TEXT, nombre TEXT)');
        $db->query('CREATE TABLE dteempresas (codigoEmpresa TEXT, nombreComercial TEXT)');
        $db->table('cattipodocumento')->insert(['codigo' => '01', 'valores' => 'Factura']);
        $db->table('puntosdeventa')->insert(['codigoPuntoVenta' => '1', 'idSucursal' => 1]);
        $db->table('sucursales')->insert(['idSucursal' => 1, 'codigoEmpresa' => '1']);
        $db->table('dteempresas')->insert(['codigoEmpresa' => '1']);
        foreach ([['2025-12-31 23:59:59', 1], ['2026-01-01 00:00:00', 1],
            ['2026-12-31 23:59:59', 4], ['2027-01-01 00:00:00', 1],
            ['2024-06-01', 2]] as [$fecha, $estado]) {
            $db->table('sellosdte')->insert(['fechaFactura' => $fecha, 'idEstadoDTE' => $estado,
                'codigoTipoDTE' => '01', 'codigoPuntoVenta' => '1', 'jsonDTE' => '{}']);
        }
        try {
            $model = new SellosdteModel($db);
            $archivo = $model->getDTEArchivo(2026);
            self::assertSame(2, $archivo['total']);
            self::assertSame(['2026-01-01 00:00:00', '2026-12-31 23:59:59'], array_column($archivo['data'], 'fechaFactura'));
            self::assertSame([2027, 2026, 2025], $model->getAniosArchivo());
            self::assertSame([], $model->getDTEArchivo(2023)['data']);
            self::assertSame($model->getDTEArchivo((int) date('Y')), $model->getDTEArchivo());
        } finally {
            $db->close();
        }
    }
}
