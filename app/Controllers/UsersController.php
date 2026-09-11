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
        if (!$this->validate([
            'user_name' => 'required|alpha_numeric_punct|min_length[3]|max_length[100]|is_unique[users.username]',
            'email' => 'required|valid_email|max_length[254]',
            'password' => 'required|min_length[15]|max_length[72]',
        ])) {
            return $this->response->setStatusCode(422)->setBody('Datos inválidos. Use un usuario único, correo válido y contraseña de 15 a 72 caracteres.');
        }
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
        if (!$this->validate([
            'user_name' => 'required|alpha_numeric_punct|min_length[3]|max_length[100]',
            'email' => 'required|valid_email|max_length[254]',
        ])) {
            return $this->response->setStatusCode(422)->setBody('Usuario o correo inválido.');
        }
        $model = new UserModel();
        if ($model->where('username', $this->request->getPost('user_name'))->where('id !=', $id)->first()) {
            return $this->response->setStatusCode(422)->setBody('El usuario ya existe.');
        }

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
