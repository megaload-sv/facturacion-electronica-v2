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

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        if (!is_string($username) || !is_string($password) || strlen($username) > 100 || strlen($password) > 1024 || $username === '' || $password === '') {
            return redirect()->to('/login')->with('error', 'Usuario o contraseña incorrectos.');
        }
        $username = trim($username);
        $throttler = service('throttler');
        $ipKey = 'login_ip_' . hash('sha256', $this->request->getIPAddress());
        $accountKey = 'login_account_' . hash('sha256', mb_strtolower($username));
        if (!$throttler->check($ipKey, 30, 900, 1) || !$throttler->check($accountKey, 10, 900, 1)) {
            return $this->response->setStatusCode(429)->setHeader('Retry-After', '900')
                ->setBody('Demasiados intentos. Intente nuevamente más tarde.');
        }

        $user = $model->where('username', $username)->first();
        //$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Perform a password verification even for unknown accounts.
        $hash = $user['password'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $validPassword = password_verify($password, $hash);
        if ($user && $validPassword) {
            $session->regenerate(true);
            service('security')->generateHash();
            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                $model->update($user['id'], ['password' => password_hash($password, PASSWORD_DEFAULT)]);
            }
            $session->set([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'logged_in' => true,
                'authenticated_at' => time(),
                'last_activity' => time(),
                'credential_version' => hash('sha256', $model->find($user['id'])['password']),
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
