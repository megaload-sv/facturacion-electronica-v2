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
        $saved = $model->save([
            'permission_name' => $this->request->getPost('permission_name'),
            'description' => $this->request->getPost('description'),
        ]);
        return redirect()->to('/permissions')->with($saved ? 'success' : 'error', $saved ? 'Permiso creado correctamente.' : 'No se pudo crear el permiso.');
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
        if (!$model->find($id)) {
            return redirect()->to('/permissions')->with('error', 'El permiso no existe.');
        }
        $saved = $model->update($id, [
            'permission_name' => $this->request->getPost('permission_name'),
            'description' => $this->request->getPost('description'),
        ]);
        return redirect()->to('/permissions')->with($saved ? 'success' : 'error', $saved ? 'Permiso actualizado correctamente.' : 'No se pudo actualizar el permiso.');
    }

    public function delete($id)
    {
        $model = new PermissionModel();
        if (!$model->find($id)) {
            return redirect()->to('/permissions')->with('error', 'El permiso no existe.');
        }
        $deleted = $model->delete($id);
        return redirect()->to('/permissions')->with($deleted ? 'success' : 'error', $deleted ? 'Permiso eliminado correctamente.' : 'No se pudo eliminar el permiso.');
    }
}
