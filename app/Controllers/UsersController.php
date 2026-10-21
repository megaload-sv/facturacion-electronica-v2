<?php
namespace App\Controllers;

use App\Libraries\AccessAssignments;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class UsersController extends BaseController
{
    public function index()
    {
        return $this->render('pages/admin/users/index', [
            'users' => (new UserModel())->orderBy('username')->findAll(),
            'assignments' => \Config\Database::connect()->table('role_user')->select('role_user.user_id, roles.role_name')->join('roles', 'roles.id = role_user.role_id')->get()->getResultArray(),
        ]);
    }
    public function create() { return $this->form(); }
    public function edit($id) { return $this->form((int) $id); }
    public function store() { return $this->saveUser(); }
    public function update($id) { return $this->saveUser((int) $id); }

    private function form(?int $id = null, array $errors = [], array $input = [])
    {
        $access = new AccessAssignments();
        $user = $id ? (new UserModel())->find($id) : null;
        if ($id !== null && !$user) { throw PageNotFoundException::forPageNotFound(); }
        return $this->render('pages/admin/users/form', $access->catalog() + [
            'user' => $user, 'errors' => $errors, 'input' => $input,
            'selectedRoles' => $input['role_ids'] ?? ($id ? $access->selected('role_user', 'user_id', $id, 'role_id') : []),
            'selectedPermissions' => $input['permission_ids'] ?? ($id ? $access->selected('permission_user', 'user_id', $id, 'permission_id') : []),
        ]);
    }

    private function saveUser(?int $id = null)
    {
        $model = new UserModel();
        if ($id !== null && !$model->find($id)) { throw PageNotFoundException::forPageNotFound(); }
        $rules = ['user_name' => 'required|alpha_numeric_punct|min_length[3]|max_length[100]', 'email' => 'required|valid_email|max_length[254]'];
        if ($id === null || ($this->request->getPost('password') ?? '') !== '') {
            $rules['password'] = 'required|min_length[15]|max_length[72]';
            $rules['password_confirm'] = 'required|matches[password]';
        }
        $valid = $this->validate($rules);
        $errors = $valid ? [] : $this->validator->getErrors();
        $input = [
            'user_name' => is_string($this->request->getPost('user_name')) ? $this->request->getPost('user_name') : '',
            'email' => is_string($this->request->getPost('email')) ? $this->request->getPost('email') : '',
            'role_ids' => [], 'permission_ids' => [],
        ];
        $access = new AccessAssignments();
        try {
            $input['role_ids'] = $access->ids($this->request->getPost('role_ids') ?? [], 'roles');
            $input['permission_ids'] = $access->ids($this->request->getPost('permission_ids') ?? [], 'permissions');
            if (!$input['role_ids']) { $errors['roles'] = 'Seleccione al menos un rol para el usuario.'; }
            if ($id === (int) session('user_id')) {
                $adminIds = array_map('intval', array_column(\Config\Database::connect()->table('roles')->whereIn('role_name', ['root', 'admin'])->get()->getResultArray(), 'id'));
                if (!array_intersect($adminIds, $input['role_ids'])) { $errors['roles'] = 'Conserve un rol administrador en su propia cuenta.'; }
            }
        } catch (\InvalidArgumentException $error) { $errors['access'] = $error->getMessage(); }
        $duplicate = $model->where('username', $input['user_name']);
        if ($id !== null) { $duplicate->where('id !=', $id); }
        if ($duplicate->first()) { $errors['user_name'] = 'Este nombre de usuario ya está en uso.'; }
        if ($errors) {
            $this->response->setStatusCode(422);
            return $this->form($id, $errors, $input);
        }
        try {
            $access->transaction(function () use ($access, $model, $id, $input) {
                $data = ['username' => $input['user_name'], 'email' => $input['email']];
                $password = $this->request->getPost('password');
                if (is_string($password) && $password !== '') { $data['password'] = password_hash($password, PASSWORD_DEFAULT); }
                if ($id === null) {
                    $userId = $model->insert($data, true);
                    if (!$userId) { throw new \RuntimeException('User insert failed'); }
                } else {
                    $userId = $id;
                    if (!$model->update($id, $data)) { throw new \RuntimeException('User update failed'); }
                }
                $access->replace('role_user', 'user_id', (int) $userId, 'role_id', $input['role_ids']);
                $access->replace('permission_user', 'user_id', (int) $userId, 'permission_id', $input['permission_ids']);
            });
        } catch (\Throwable $error) {
            log_message('error', 'Unable to save user access: {message}', ['message' => $error->getMessage()]);
            $this->response->setStatusCode(422);
            return $this->form($id, ['save' => 'No se guardaron los cambios. Inténtelo nuevamente.'], $input);
        }
        return redirect()->to(site_url('users'))->with('success', 'Usuario y accesos guardados correctamente.');
    }

    public function delete($id)
    {
        if ((int) $id === (int) session('user_id')) {
            return redirect()->to(site_url('users'))->with('error', 'No puede eliminar su propia cuenta.');
        }
        $access = new AccessAssignments();
        $access->transaction(function () use ($access, $id) {
            $access->replace('role_user', 'user_id', (int) $id, 'role_id', []);
            $access->replace('permission_user', 'user_id', (int) $id, 'permission_id', []);
            if (!(new UserModel())->delete($id)) { throw new \RuntimeException('User delete failed'); }
        });
        return redirect()->to(site_url('users'))->with('success', 'Usuario eliminado.');
    }
}
