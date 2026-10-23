<?php

namespace App\Controllers;

use App\Models\MenuModel;
use App\Models\PermissionMenuModel;
class PermissionMenuController extends BaseController
{
    protected $permissionMenuModel;
    protected $menuModel;

    public function __construct()
    {

        $this->permissionMenuModel = new PermissionMenuModel();
        $this->menuModel = new MenuModel();
    }

    public function index()
    {


        $data['menu_items'] = $this->permissionMenuModel->getAllMenuItems();
        $data['permissions'] = $this->permissionMenuModel->getAllPermissions();
        return $this->render('pages/admin/permission_menu/index', $data);
    }

    public function edit($menu_item_id)
    {
        $data['menu_item'] = $this->menuModel->find($menu_item_id);
        if (!$data['menu_item']) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $data['permissions'] = $this->permissionMenuModel->getAllPermissions();
        $data['assigned_permissions'] = $this->permissionMenuModel->getPermissionsForMenu($menu_item_id);

        //var_dump($data);

        return $this->render('pages/admin/permission_menu/edit', $data);
    }

    public function store()
    {
        $menu_item_id = $this->request->getPost('menu_item_id');
        $permissions = $this->request->getPost('permissions'); // Lista de permisos seleccionados

        // Eliminar todos los permisos anteriores
        $this->permissionMenuModel->where('menu_item_id', $menu_item_id)->delete();

        // Insertar los nuevos permisos seleccionados
        if ($permissions) {
            foreach ($permissions as $permission_id) {
                $this->permissionMenuModel->assignPermission([
                    'permission_id' => $permission_id,
                    'menu_item_id' => $menu_item_id,
                ]);
            }
        }

        return redirect()->to('/permission_menu');
    }

    public function delete($permission_id, $menu_item_id)
    {
        $this->permissionMenuModel->removePermission($permission_id, $menu_item_id);
        return redirect()->to('/permission_menu');
    }
}
