<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJsonProcessTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'group_name' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
            ],
            'folder_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'processed_files_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'excel_file_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('group_name');
        $this->forge->createTable('json_process_groups');

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'group_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'file_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'numero_control' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'generation_code' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => true,
            ],
            'issue_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'issuer_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'receiver_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'total_pagar' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'iva_13' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'fovial' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'cotrans' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'estado_hacienda' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            'codigo_msg_hacienda' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
            ],
            'descripcion_msg_hacienda' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'observaciones_hacienda' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'sello_recibido' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'empleado' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'no_unico' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'observacion' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'sucursal' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'moved_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('group_id');
        $this->forge->addForeignKey('group_id', 'json_process_groups', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('json_process_files');
    }

    public function down()
    {
        $this->forge->dropTable('json_process_files', true);
        $this->forge->dropTable('json_process_groups', true);
    }

}
