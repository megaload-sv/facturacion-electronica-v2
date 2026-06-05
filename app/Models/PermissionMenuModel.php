<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionMenuModel extends Model
{
    protected $table = 'permission_menu';
    protected $primaryKey = ['permission_id', 'menu_item_id'];
    protected $allowedFields = ['permission_id', 'menu_item_id'];
    public $useAutoIncrement = false;

    public function getPermissionsForMenu($menu_item_id)
    {
        return $this->where('menu_item_id', $menu_item_id)->findAll();
    }

    public function getAllPermissions()
    {
        return $this->db->table('permissions')->get()->getResultArray();
    }

    public function getAllMenuItems()
    {
        return $this->db->table('menu_items')->get()->getResultArray();
    }
    public function getAllMenuItemsbyId($id)
    {
        $this->where('id', $menu_item_id)->findAll();
        return $this->db->table('menu_items')->get()->getResultArray();
    }

    public function assignPermission($data)
    {
        return $this->insert($data);
    }

    public function removePermission($permission_id, $menu_item_id)
    {
        return $this->where('permission_id', $permission_id)
            ->where('menu_item_id', $menu_item_id)
            ->delete();
    }
}
