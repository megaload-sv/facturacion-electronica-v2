<?php

namespace App\Controllers;

use App\Models\MenuModel;
use App\Models\RoleModel;
use CodeIgniter\Controller;

class RolesController extends BaseController
{
    protected MenuModel $_menuModel;

    public function __construct()
    {
        $this->_menuModel = new MenuModel();
    }

    public function index()
    {

        // Obtener el ID del usuario logueado desde la sesión
        $userId = session()->get('user_id');

        // Obtener el menú para el usuario actual
        $menu = $this->_menuModel->getMenuByUser($userId);

        $model = new RoleModel();
        $data['roles'] = $model->findAll();
        $data['menu'] = $menu;
        return $this->render('pages/admin/roles/index', $data);
    }

    public function create()
    {
        return $this->render('pages/admin/roles/create');
    }

    public function store()
    {
        $model = new RoleModel();
        $model->save([
            'role_name' => $this->request->getPost('role_name'),
            'description' => $this->request->getPost('description'),
        ]);
        return redirect()->to('/roles');
    }

    public function edit($id)
    {
        $model = new RoleModel();
        $data['role'] = $model->find($id);
        return $this->render('pages/admin/roles/edit', $data);
    }

    public function update($id)
    {
        $model = new RoleModel();
        $model->update($id, [
            'role_name' => $this->request->getPost('role_name'),
            'description' => $this->request->getPost('description'),
        ]);
        return redirect()->to('/roles');
    }

    public function delete($id)
    {
        $model = new RoleModel();
        $model->delete($id);
        return redirect()->to('/roles');
    }
}
