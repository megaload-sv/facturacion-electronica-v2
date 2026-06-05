<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuModel extends Model
{
    protected $table = 'menu_items';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'url', 'parent_id', 'order', 'icon', 'role_id'];

    // Obtener el menú basado en los roles del usuario
    public function getMenuByUser($userId)
    {
        $db = \Config\Database::connect();

        // Consultar los ítems del menú accesibles por los roles del usuario
        $builder = $db->table('role_user')
            ->select('menu_items.*')
            ->join('role_menu', 'role_menu.role_id = role_user.role_id')
            ->join('menu_items', 'menu_items.id = role_menu.menu_item_id')
            ->where('role_user.user_id', $userId)
            ->orderBy('menu_items.parent_id, menu_items.order', 'ASC');

        $menuItems = $builder->get()->getResultArray();

        // Organizar el menú de forma jerárquica
        return $this->buildMenuHierarchy($menuItems);
    }

    // Función recursiva para organizar el menú en jerarquía
    private function buildMenuHierarchy($menuItems, $parentId = 0)
    {
        $menu = [];
        foreach ($menuItems as $menuItem) {
            if ($menuItem['parent_id'] == $parentId) {
                $children = $this->buildMenuHierarchy($menuItems, $menuItem['id']);
                if ($children) {
                    $menuItem['children'] = $children;
                }
                $menu[] = $menuItem;
            }
        }
        return $menu;
    }

    public function getMenuHierarchy()
    {
        $builder = $this->db->table($this->table);
        $builder->orderBy('parent_id, `order` ASC');
        $query = $builder->get();

        return $query->getResultArray();
    }

    public function getMenuById($id)
    {
        $builder = $this->db->table('menu_items')
        ->select('menu_items.*, role_menu.role_id') 
        ->join('role_menu', 'role_menu.menu_item_id = menu_items.id') 
        ->where('menu_items.id', $id); 

        // Obtener el resultado
        return $builder->get()->getRowArray();
    }

    public function createMenuItem($data)
    {
        $this->insert($data);
        return $this->getInsertID();
    }

    public function updateMenuItem($id, $data)
    {
        return $this->update($id, $data);
    }

    public function deleteMenuItem($id)
    {
        return $this->delete($id);
    }
    public function insertRolesForMenuItem($menuItemId, $roles)
    {
        // Crear el Query Builder para la tabla role_menu
        $builder = $this->db->table('role_menu');

        $batchData = [];

        foreach ($roles as $roleId) {
            $batchData[] = [
                'menu_item_id' => $menuItemId,
                'role_id' => $roleId
            ];
        }

        // Insertar múltiples filas en role_menu
        if (!empty($batchData)) {
            $builder->insertBatch($batchData);
        }
    }
    
    public function updateRolesForMenuItem($menuItemId, $roles)
    {
        
        // Crear el Query Builder para la tabla role_menu
        $builder = $this->db->table('role_menu');

        // Primero, eliminar los roles antiguos asociados al menú
        $builder->where('menu_item_id', $menuItemId)->delete();

        // Preparar los nuevos datos para insertar
        $batchData = [];

        foreach ($roles as $roleId) {
            $batchData[] = [
                'menu_item_id' => $menuItemId,
                'role_id' => $roleId
            ];
        }

        // Insertar los nuevos roles para el ítem de menú
        if (!empty($batchData)) {
            $builder->insertBatch($batchData);
        }
    }
}
