<?php
namespace App\Libraries;

use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class AccessAssignments
{
    public function catalog(): array
    {
        $db = Database::connect();
        return [
            'roles' => $db->table('roles')->orderBy('role_name')->get()->getResultArray(),
            'permissions' => $db->table('permissions')->where('permission_name !=', 'security.manage')->orderBy('permission_name')->get()->getResultArray(),
            'rolePermissions' => $db->table('permission_role')->get()->getResultArray(),
        ];
    }

    public function ids($value, string $table): array
    {
        if (!is_array($value)) { throw new InvalidArgumentException('La selección de accesos no es válida.'); }
        $ids = [];
        foreach ($value as $id) {
            if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) {
                throw new InvalidArgumentException('La selección de accesos no es válida.');
            }
            $ids[] = (int) $id;
        }
        $ids = array_values(array_unique($ids));
        if ($ids) {
            $query = Database::connect()->table($table)->whereIn('id', $ids);
            if ($table === 'permissions') { $query->where('permission_name !=', 'security.manage'); }
            if ($query->countAllResults() !== count($ids)) {
                throw new InvalidArgumentException('Uno de los roles o permisos seleccionados ya no está disponible.');
            }
        }
        return $ids;
    }

    public function selected(string $table, string $owner, int $id, string $column): array
    {
        return array_map('intval', array_column(Database::connect()->table($table)->where($owner, $id)->get()->getResultArray(), $column));
    }

    public function replace(string $table, string $owner, int $id, string $column, array $ids): void
    {
        $db = Database::connect();
        if (!$db->table($table)->where($owner, $id)->delete()) { throw new RuntimeException('Access delete failed'); }
        foreach ($ids as $value) {
            if (!$db->table($table)->insert([$owner => $id, $column => $value])) { throw new RuntimeException('Access insert failed'); }
        }
    }

    public function transaction(callable $save): void
    {
        $db = Database::connect();
        $db->transBegin();
        try {
            $save();
            if (!$db->transStatus()) { throw new RuntimeException('Transaction failed'); }
            $db->transCommit();
        } catch (\Throwable $error) {
            $db->transRollback();
            throw $error;
        }
    }
}
