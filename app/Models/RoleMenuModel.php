<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleMenuModel extends Model
{
    protected $table = 'role_menu';
   // protected $primaryKey = ['role_id', 'menu_item_id'];
    protected $allowedFields = ['role_id', 'menu_item_id'];
    public $useAutoIncrement = false;

    public function getMenuItemsForRole($role_id)
    {
        return $this->where('role_id', $role_id)->findAll();
    }

    public function getAllRoles()
    {
        return $this->db->table('roles')->get()->getResultArray();
    }

    public function getAllMenuItems()
    {
        return $this->db->table('menu_items')->get()->getResultArray();
    }

    public function assignMenuToRole($data)
    {
        $builder = $this->db->table($this->table);
        return $builder->insert($data);
    }

    public function removeMenuFromRole($role_id, $menu_item_id)
    {
        return $this->where('role_id', $role_id)
            ->where('menu_item_id', $menu_item_id)
            ->delete();
    }

    public function getRoleById($id)
    {
        $builder = $this->db->table('roles')
            ->where('id', $id);

        // Obtener el resultado
        return $builder->get()->getRowArray();
    }
}