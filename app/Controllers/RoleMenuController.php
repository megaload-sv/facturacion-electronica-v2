<?php

namespace App\Controllers;

use App\Models\RoleMenuModel;
use CodeIgniter\Controller;
use App\Models\MenuModel;

class RoleMenuController extends BaseController
{
    protected $roleMenuModel;

    public function __construct()
    {
        $this->roleMenuModel = new RoleMenuModel();
    }

    public function index()
    {
        
        
        $data['roles'] = $this->roleMenuModel->getAllRoles();
        $data['menu_items'] = $this->roleMenuModel->getAllMenuItems();
        return $this->render('pages/admin/role_menu/index', $data);
    }

    public function edit($role_id)
    {
        $data['role'] = $this->roleMenuModel->getRoleById($role_id); // Obtener todos los roles
        $data['menu_items'] = $this->roleMenuModel->getAllMenuItems();
        $data['assigned_menu_items'] = $this->roleMenuModel->getMenuItemsForRole($role_id);
        return $this->render('pages/admin/role_menu/edit', $data);
    }

    public function store()
    {
        $role_id = $this->request->getPost('role_id');
        $menu_items = $this->request->getPost('menu_items'); // Lista de ítems del menú seleccionados

        // Eliminar todos los ítems de menú anteriores asignados a este rol
        $this->roleMenuModel->where('role_id', $role_id)->delete();   
       
        // Insertar los nuevos ítems del menú seleccionados
        if ($menu_items) {
            foreach ($menu_items as $menu_item_id) {
                $this->roleMenuModel->assignMenuToRole([
                    'role_id' => $role_id,
                    'menu_item_id' => $menu_item_id,
                ]);
            }
        }

        return redirect()->to('/role_menu');
    }

    public function delete($role_id, $menu_item_id)
    {
        $this->roleMenuModel->removeMenuFromRole($role_id, $menu_item_id);
        return redirect()->to('/role_menu');
    }
}
