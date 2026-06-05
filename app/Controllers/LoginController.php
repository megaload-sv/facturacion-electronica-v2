<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;

class LoginController extends Controller
{
    public function index(){

        $session = session();
        if ($session->get('logged_in')) {
            return redirect()->to('/dashboard');
        }
        return view('login');
    }

    public function authenticate()
    {
        $session = session();
        $model = new UserModel();

        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');

        $user = $model->where('username', $username)->first();
        //$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($user && password_verify($password, $user['password'])) {
            $session->set([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'logged_in' => true,
            ]);
            return redirect()->to('/dashboard');
        } else {
            $session->setFlashdata('error', 'usuario o password incorrectos');
            return redirect()->to('/login');
        }
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}