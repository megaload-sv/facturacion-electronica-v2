<?php

use App\Database\Migrations\CreateInvalidationRules;
use App\Services\InvalidacionPlazos;
use PHPUnit\Framework\TestCase;

final class InvalidacionPlazosTest extends TestCase
{
    private $db;
    private InvalidacionPlazos $servicio;

    protected function setUp(): void
    {
        $this->db = \Config\Database::connect([
            'DBDriver' => 'SQLite3', 'database' => ':memory:', 'DBPrefix' => '',
        ], false);
        require_once APPPATH . 'Database/Migrations/2026-10-21-120000_CreateInvalidationRules.php';
        (new CreateInvalidationRules(\Config\Database::forge($this->db)))->up();
        $this->servicio = new InvalidacionPlazos($this->db);
    }

    protected function tearDown(): void
    {
        $this->db->close();
    }

    private function dte(string $tipo = '01'): array
    {
        return ['tipoDte' => $tipo, 'fechaEmision' => '2026-07-21',
            'fechaProcesamiento' => '21/07/2026 08:00:00', 'selloRecibido' => 'SELLO',
            'codigoGeneracion' => '12345678-1234-4234-8234-123456789ABC'];
    }

    private function ahora(string $fecha): DateTimeImmutable
    {
        return new DateTimeImmutable($fecha, new DateTimeZone('America/El_Salvador'));
    }

    public function testAllReasonsAllowLastSecondAndRejectNextDay(): void
    {
        foreach (['01', '11', '14'] as $tipo) {
            foreach (['1', '2', '3'] as $motivo) {
                $resultado = $this->servicio->evaluar($this->dte($tipo), $motivo, $this->ahora('2026-10-21 23:59:59'));
                self::assertTrue($resultado['permitido']);
                self::assertSame('2026-10-21 23:59:59', $resultado['limiteTransmision']);
                self::assertFalse($this->servicio->evaluar($this->dte($tipo), $motivo, $this->ahora('2026-10-22'))['permitido']);
            }
        }
    }

    public function testCalendarMonthsClampToLastDayIncludingLeapYear(): void
    {
        self::assertSame('2027-02-28', $this->servicio->limite($this->ahora('2026-11-30'), 'meses', 3)->format('Y-m-d'));
        self::assertSame('2028-02-29', $this->servicio->limite($this->ahora('2027-11-30'), 'meses', 3)->format('Y-m-d'));
    }

    public function testGenerationDateAlsoRestrictsInvoice(): void
    {
        $dte = $this->dte();
        $dte['fechaEmision'] = '2026-07-20';
        self::assertFalse($this->servicio->evaluar($dte, '1', $this->ahora('2026-10-21'))['permitido']);
    }

    public function testBusinessDaysUseExplicitHolidaysAndPreserveEventGenerationDate(): void
    {
        for ($i = 1; $i <= 31; $i++) {
            $dia = $this->ahora(sprintf('2026-08-%02d', $i));
            $this->db->table('dte_calendario')->insert(['fecha' => $dia->format('Y-m-d'),
                'habil' => (int) ($dia->format('N') < 6 && $i !== 6), 'descripcion' => 'Calendario de prueba']);
        }
        $resultado = $this->servicio->evaluar($this->dte('03'), '2', $this->ahora('2026-08-17 23:59:59'));
        self::assertTrue($resultado['permitido']);
        self::assertSame('2026-08-17 23:59:59', $resultado['limiteTransmision']);
        self::assertSame('2026-07-21', $resultado['fechaEvento']);
        self::assertFalse($this->servicio->evaluar($this->dte('03'), '2', $this->ahora('2026-08-18'))['permitido']);
    }

    public function testMissingCalendarBlocksInsteadOfGuessing(): void
    {
        $resultado = $this->servicio->evaluar($this->dte('04'), '1', $this->ahora('2026-08-01'));
        self::assertFalse($resultado['permitido']);
        self::assertStringContainsString('calendario', $resultado['message']);
    }

    public function testOfficialCalendarAccountsForHolyWeekAndNovemberHoliday(): void
    {
        require_once APPPATH . 'Database/Migrations/2026-10-21-120100_LoadDteCalendar2026.php';
        (new \App\Database\Migrations\LoadDteCalendar2026(\Config\Database::forge($this->db)))->up();
        self::assertSame('2026-04-20', $this->servicio->limite($this->ahora('2026-03-19'), 'habiles_mes_siguiente', 10)->format('Y-m-d'));
        self::assertSame('2026-11-16', $this->servicio->limite($this->ahora('2026-10-21'), 'habiles_mes_siguiente', 10)->format('Y-m-d'));
    }

    public function testOverlappingOrMissingRulesBlock(): void
    {
        $regla = $this->db->table('dte_invalidacion_reglas')->where('tipo_dte', '01')->where('condicion', 'general')->get()->getRowArray();
        unset($regla['id']);
        $this->db->table('dte_invalidacion_reglas')->insert($regla);
        self::assertFalse($this->servicio->evaluar($this->dte(), '1', $this->ahora('2026-10-21'))['permitido']);
        self::assertFalse($this->servicio->evaluar($this->dte('99'), '1', $this->ahora('2026-10-21'))['permitido']);
    }

    public function testRuleChangesAndReasonOverridesComeFromDatabase(): void
    {
        $regla = $this->db->table('dte_invalidacion_reglas')->where('tipo_dte', '01')->where('condicion', 'general')->get()->getRowArray();
        unset($regla['id']);
        $regla['motivo'] = '2';
        $regla['transmision_cantidad'] = 6;
        $regla['evento_cantidad'] = 6;
        $regla['vigente_desde'] = '2026-10-22';
        $this->db->table('dte_invalidacion_reglas')->insert($regla);
        self::assertTrue($this->servicio->evaluar($this->dte(), '2', $this->ahora('2026-10-21'))['permitido']);
        self::assertTrue($this->servicio->evaluar($this->dte(), '2', $this->ahora('2026-10-22'))['permitido']);
        self::assertFalse($this->servicio->evaluar($this->dte(), '1', $this->ahora('2026-10-22'))['permitido']);
        $this->db->table('dte_invalidacion_reglas')->where('motivo', '2')->update(['vigente_hasta' => '2026-10-22']);
        self::assertFalse($this->servicio->evaluar($this->dte(), '2', $this->ahora('2026-10-23'))['permitido']);
    }

    public function testPharmaceuticalExceptionRequiresTrustedClassification(): void
    {
        $this->db->table('dte_invalidacion_contextos')->insert([
            'codigo_generacion' => $this->dte()['codigoGeneracion'],
            'condicion' => 'farmaceutica_perecedera', 'justificacion' => 'Prueba',
        ]);
        self::assertTrue($this->servicio->evaluar($this->dte(), '2', $this->ahora('2027-10-21'))['permitido']);
        self::assertFalse($this->servicio->evaluar($this->dte('14'), '2', $this->ahora('2027-10-21'))['permitido']);
    }

    public function testMalformedDatesMissingSealAndInvalidReasonBlock(): void
    {
        foreach (['fechaEmision' => '2026-02-30', 'fechaProcesamiento' => '', 'selloRecibido' => ''] as $campo => $valor) {
            $dte = $this->dte();
            $dte[$campo] = $valor;
            self::assertFalse($this->servicio->evaluar($dte, '1', $this->ahora('2026-10-21'))['permitido']);
        }
        self::assertFalse($this->servicio->evaluar($this->dte(), '9', $this->ahora('2026-10-21'))['permitido']);
    }
}
