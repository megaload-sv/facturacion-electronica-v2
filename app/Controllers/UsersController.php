<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\MenuModel;
use CodeIgniter\Controller;

class UsersController extends BaseController
{
    public function index()
    {
        $model = new UserModel();
        $data['users'] = $model->findAll();

        // Usar $this->render() para pasar el menú automáticamente
        return $this->render('pages/admin/users/index', $data);
    }

    public function create()
    {
        return $this->render('pages/admin/users/create');
    }

    public function store()
    {
        $model = new UserModel();

        // Guardar los datos enviados desde el formulario
        $model->save([
            'username' => $this->request->getPost('user_name'),
            'email' => $this->request->getPost('email'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT), // Encriptar la contraseña
        ]);

        return redirect()->to('/users');
    }

    public function edit($id)
    {
        $model = new UserModel();
        $data['user'] = $model->find($id);


        return $this->render('pages/admin/users/edit', $data);
    }

    public function update($id)
    {
        $model = new UserModel();

        // Actualizar los datos del usuario
        $model->update($id, [
            'username' => $this->request->getPost('user_name'),
            'email' => $this->request->getPost('email'),
        ]);

        return redirect()->to('/users');
    }

    public function delete($id)
    {
        $model = new UserModel();
        $model->delete($id);

        return redirect()->to('/users');
    }
}
