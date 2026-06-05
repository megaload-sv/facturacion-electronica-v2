<?php

namespace App\Controllers;

use App\Models\MenuModel;
use App\Models\RoleModel;


class MenuController extends BaseController
{

    protected MenuModel $_menuModel;

    public function __construct()
    {
        parent::__construct();
        $this->_menuModel = new MenuModel();

    }

    public function getMenu()
    {
        return $this->render('partials/menu');
    }

    public function index()
    {
        $data['menus'] = $this->menuModel->getMenuHierarchy();

        // Obtener el ID del usuario logueado desde la sesión
        $userId = session()->get('user_id');


        $menu = $menuModel->getMenuByUser($userId);
        $data['menu'] = $menu;
        return $this->render('pages/admin/menu/index', $data);
    }

    public function create()
    {
        $roleModel = new RoleModel();
        $data['roles'] = $roleModel->findAll();
        $data['menus'] = $this->menuModel->getMenuHierarchy(); // Cargar todos los menús
        // Obtener el ID del usuario logueado desde la sesión
        $userId = session()->get('user_id');

        // Obtener el menú para el usuario actual
        $menuModel = new MenuModel();
        $menu = $menuModel->getMenuByUser($userId);
        $data['menu'] = $menu;
        return $this->render('pages/admin/menu/create', $data);
    }

    public function store()
    {
        // Obtener los datos enviados por POST (menu_items y roles[])
        $data = $this->request->getPost();

        // Verificar si el parent_id viene como 0, en ese caso, cambiarlo a null
        if (isset($data['parent_id']) && $data['parent_id'] == 0) {
            $data['parent_id'] = null;
        }

        // Extraer los roles[] del request
        $roles = $this->request->getPost('roles');

        // Insertar los datos en la tabla menu_items
        $menuItemId = $this->menuModel->createMenuItem($data);

        // Verificar si el insert fue exitoso y tenemos un nuevo ID
        if ($menuItemId && !empty($roles)) {
            // Llamar a un método en el modelo para insertar los roles con el ultimo ID generado en menu_items
            $this->menuModel->insertRolesForMenuItem($menuItemId, $roles);
        }

        return redirect()->to('/menu');
    }

    public function edit($id)
    {
        $roleModel = new RoleModel();
        $data['roles'] = $roleModel->findAll();
        $data['menuEdit'] = $this->menuModel->getMenuById($id); // Cargar el menú a editar
        $data['menus'] = $this->menuModel->getMenuHierarchy(); // Cargar todos los menús
        // Obtener el ID del usuario logueado desde la sesión
        $userId = session()->get('user_id');

        // Obtener el menú para el usuario actual
        $menuModel = new MenuModel();
        $menu = $menuModel->getMenuByUser($userId);
        $data['menu'] = $menu;

        return $this->render('pages/admin/menu/edit', $data);
    }

    public function update($id)
    {
        // Obtener los datos enviados por POST (incluyendo roles[])
        $data = $this->request->getPost();

        if (isset($data['parent_id']) && $data['parent_id'] == 0) {
            $data['parent_id'] = null;
        }
        // Extraer los roles[] del request
        $roles = $this->request->getPost('roles');

        // Actualizar los datos en la tabla menu_items
        $this->menuModel->updateMenuItem($id, $data);

        // Si hay roles enviados, actualizarlos en la tabla role_menu
        if (!empty($roles)) {
            // Llamar a un método en el modelo para actualizar los roles
            $this->menuModel->updateRolesForMenuItem($id, $roles);
        }

        // Redirigir al listado del menú
        return redirect()->to('/menu');
    }

    public function delete($id)
    {
        $this->menuModel->deleteMenuItem($id);
        return redirect()->to('/menu');
    }
}
