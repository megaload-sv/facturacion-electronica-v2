<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SecurityPermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            'invoices.view' => 'Consultar facturas, dashboard, reportes y descargar documentos',
            'invoices.process' => 'Procesar y enviar DTE a Hacienda',
            'invoices.resend' => 'Reenviar DTE y correos',
            'invoices.invalidate' => 'Invalidar DTE',
            'imports.manage' => 'Importar, exportar y eliminar grupos JSON',
        ];
        foreach ($permissions as $name => $description) {
            if ($this->db->table('permissions')->where('permission_name', $name)->countAllResults() === 0) {
                $this->db->table('permissions')->insert([
                    'permission_name' => $name, 'description' => $description,
                    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
        // Deliberately grants nothing: an administrator assigns business permissions.
    }
}
