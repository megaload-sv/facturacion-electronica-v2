<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInvalidationRules extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'tipo_dte' => ['type' => 'VARCHAR', 'constraint' => 2],
            'motivo' => ['type' => 'VARCHAR', 'constraint' => 1, 'default' => '*'],
            'condicion' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'general'],
            'version' => ['type' => 'VARCHAR', 'constraint' => 20],
            'vigente_desde' => ['type' => 'DATE'],
            'vigente_hasta' => ['type' => 'DATE', 'null' => true],
            'transmision_calculo' => ['type' => 'VARCHAR', 'constraint' => 40],
            'transmision_cantidad' => ['type' => 'INT'],
            'evento_calculo' => ['type' => 'VARCHAR', 'constraint' => 40],
            'evento_cantidad' => ['type' => 'INT'],
            'fecha_evento_origen' => ['type' => 'VARCHAR', 'constraint' => 20],
            'referencia' => ['type' => 'VARCHAR', 'constraint' => 500],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tipo_dte', 'vigente_desde']);
        $this->forge->createTable('dte_invalidacion_reglas');

        // Calendario explícito: una fila por día, incluidos fines de semana.
        // Una fecha ausente no se supone hábil: requiere completar el calendario.
        $this->forge->addField([
            'fecha' => ['type' => 'DATE'],
            'habil' => ['type' => 'BOOLEAN'],
            'descripcion' => ['type' => 'VARCHAR', 'constraint' => 255],
        ]);
        $this->forge->addKey('fecha', true);
        $this->forge->createTable('dte_calendario');

        // Clasificación verificada del documento; nunca proviene del formulario.
        $this->forge->addField([
            'codigo_generacion' => ['type' => 'VARCHAR', 'constraint' => 36],
            'condicion' => ['type' => 'VARCHAR', 'constraint' => 40],
            'justificacion' => ['type' => 'VARCHAR', 'constraint' => 500],
        ]);
        $this->forge->addKey('codigo_generacion', true);
        $this->forge->createTable('dte_invalidacion_contextos');

        foreach (['01', '11', '14', '03', '04', '05', '06', '07', '08', '09', '15'] as $tipo) {
            $factura = in_array($tipo, ['01', '11', '14'], true);
            $this->db->table('dte_invalidacion_reglas')->insert([
                'tipo_dte' => $tipo, 'motivo' => '*', 'version' => '2.0',
                'vigente_desde' => '2026-05-25', 'vigente_hasta' => null,
                'transmision_calculo' => $factura ? 'meses' : 'habiles_mes_siguiente',
                'transmision_cantidad' => $factura ? 3 : 10,
                'evento_calculo' => $factura ? 'meses' : 'dias',
                'evento_cantidad' => $factura ? 3 : 1,
                'fecha_evento_origen' => $factura ? 'actual' : 'generacion',
                'referencia' => 'Normativa DTE 2.0, 25/05/2026, cuadro 6 y apartados 13.1.1–13.1.1.1, pp. 28–29. Regla general; excluye excepción farmacéutica.',
            ]);
        }
        foreach (['01', '11'] as $tipo) {
            $this->db->table('dte_invalidacion_reglas')->insert([
                'tipo_dte' => $tipo, 'motivo' => '*', 'condicion' => 'farmaceutica_perecedera',
                'version' => '2.0', 'vigente_desde' => '2026-05-25', 'vigente_hasta' => null,
                'transmision_calculo' => 'meses', 'transmision_cantidad' => 24,
                'evento_calculo' => 'meses', 'evento_cantidad' => 24,
                'fecha_evento_origen' => 'actual',
                'referencia' => 'Normativa DTE 2.0, cuadro 6, numeral 3: medicamentos y especialidades farmacéuticas perecederas para uso y consumo humano.',
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('dte_invalidacion_contextos');
        $this->forge->dropTable('dte_calendario');
        $this->forge->dropTable('dte_invalidacion_reglas');
    }
}
