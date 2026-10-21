<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use DateTimeImmutable;

class LoadDteCalendar2026 extends Migration
{
    public function up()
    {
        // Fuente: https://www.mh.gob.sv/wp-content/uploads/2025/12/Calendario-Tributario-2026.pdf
        $asuetos = ['01-01', '01-02', '03-30', '03-31', '04-01', '04-02', '04-03',
            '04-04', '04-05', '04-06', '05-01', '06-17', '08-03', '08-04', '08-05',
            '08-06', '09-15', '11-02', '12-24', '12-25', '12-26', '12-27', '12-28',
            '12-29', '12-30', '12-31'];
        for ($dia = new DateTimeImmutable('2026-01-01'); $dia->format('Y') === '2026'; $dia = $dia->modify('+1 day')) {
            $asueto = in_array($dia->format('m-d'), $asuetos, true);
            $this->db->table('dte_calendario')->insert([
                'fecha' => $dia->format('Y-m-d'),
                'habil' => (int) (!$asueto && (int) $dia->format('N') < 6),
                'descripcion' => $asueto ? 'Asueto: Calendario Tributario MH 2026'
                    : ((int) $dia->format('N') >= 6 ? 'Fin de semana' : 'Hábil: Calendario Tributario MH 2026'),
            ]);
        }
    }

    public function down()
    {
        $this->db->table('dte_calendario')->where('fecha >=', '2026-01-01')->where('fecha <=', '2026-12-31')->delete();
    }
}
