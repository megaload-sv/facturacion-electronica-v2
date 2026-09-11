<?php

namespace App\Controllers;

use App\Models\PermissionModel;
use CodeIgniter\Controller;

class PermissionsController extends BaseController
{
    public function index()
    {
        $model = new PermissionModel();
        $data['permissions'] = $model->findAll();
        return $this->render('pages/admin/permissions/index', $data);
    }

    public function create()
    {
        return $this->render('pages/admin/permissions/create');
    }

    public function store()
    {
        $model = new PermissionModel();
        $model->save([
            'permission_name' => $this->request->getPost('permission_name'),
            'description' => $this->request->getPost('description'),
        ]);
        return redirect()->to('/permissions');
    }

    public function edit($id)
    {
        $model = new PermissionModel();
        $data['permission'] = $model->find($id);
        return $this->render('pages/admin/permissions/edit', $data);
    }

    public function update($id)
    {
        $model = new PermissionModel();
        $model->update($id, [
            'permission_name' => $this->request->getPost('permission_name'),
            'description' => $this->request->getPost('description'),
        ]);
        return redirect()->to('/permissions');
    }

    public function delete($id)
    {
        $model = new PermissionModel();
        $model->delete($id);
        return redirect()->to('/permissions');
    }
}
