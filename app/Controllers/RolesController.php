<?php
namespace App\Controllers;

use App\Libraries\AccessAssignments;
use App\Models\RoleModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class RolesController extends BaseController
{
    public function index() { return $this->render('pages/admin/roles/index', ['roles' => (new RoleModel())->orderBy('role_name')->findAll()]); }
    public function create() { return $this->form(); }
    public function edit($id) { return $this->form((int) $id); }
    public function store() { return $this->saveRole(); }
    public function update($id) { return $this->saveRole((int) $id); }

    private function form(?int $id = null, array $errors = [], array $input = [])
    {
        $access = new AccessAssignments();
        $role = $id ? (new RoleModel())->find($id) : null;
        if ($id !== null && !$role) { throw PageNotFoundException::forPageNotFound(); }
        return $this->render('pages/admin/roles/form', $access->catalog() + [
            'role' => $role, 'input' => $input, 'errors' => $errors,
            'selectedPermissions' => $input['permission_ids'] ?? ($id ? $access->selected('permission_role', 'role_id', $id, 'permission_id') : []),
        ]);
    }

    private function saveRole(?int $id = null)
    {
        $model = new RoleModel();
        $role = $id ? $model->find($id) : null;
        if ($id !== null && !$role) { throw PageNotFoundException::forPageNotFound(); }
        $valid = $this->validate(['role_name' => 'required|alpha_dash|min_length[3]|max_length[50]', 'description' => 'permit_empty|max_length[255]']);
        $errors = $valid ? [] : $this->validator->getErrors();
        $input = ['role_name' => is_string($this->request->getPost('role_name')) ? $this->request->getPost('role_name') : '', 'description' => is_string($this->request->getPost('description')) ? $this->request->getPost('description') : '', 'permission_ids' => []];
        if (in_array(strtolower($input['role_name']), ['admin', 'root'], true) && ($role['role_name'] ?? '') !== $input['role_name']) {
            $errors['role_name'] = 'Los nombres admin y root están reservados para los roles del sistema.';
        }
        if ($role && in_array($role['role_name'], ['admin', 'root'], true) && $input['role_name'] !== $role['role_name']) {
            $errors['role_name'] = 'El nombre de este rol del sistema no se puede modificar.';
        }
        $duplicate = $model->where('role_name', $input['role_name']);
        if ($id) { $duplicate->where('id !=', $id); }
        if ($duplicate->first()) { $errors['role_name'] = 'Ya existe un rol con este nombre.'; }
        $access = new AccessAssignments();
        try { $input['permission_ids'] = $access->ids($this->request->getPost('permission_ids') ?? [], 'permissions'); }
        catch (\InvalidArgumentException $error) { $errors['access'] = $error->getMessage(); }
        if (!$errors) {
            try {
                $access->transaction(function () use ($access, $model, $id, $input) {
                    $data = ['role_name' => $input['role_name'], 'description' => $input['description']];
                    $roleId = $id ?? $model->insert($data, true);
                    if (!$roleId || ($id && !$model->update($id, $data))) { throw new \RuntimeException('Role save failed'); }
                    $access->replace('permission_role', 'role_id', (int) $roleId, 'permission_id', $input['permission_ids']);
                });
                return redirect()->to(site_url('roles'))->with('success', 'Rol y permisos guardados. Los accesos de sus usuarios se actualizaron.');
            } catch (\Throwable $error) {
                log_message('error', 'Unable to save role: {message}', ['message' => $error->getMessage()]);
                $errors['save'] = 'No se guardaron los cambios. Inténtelo nuevamente.';
            }
        }
        $this->response->setStatusCode(422);
        return $this->form($id, $errors, $input);
    }

    public function delete($id)
    {
        $model = new RoleModel();
        $role = $model->find($id);
        if (!$role) { throw PageNotFoundException::forPageNotFound(); }
        $db = \Config\Database::connect();
        if (in_array($role['role_name'], ['root', 'admin'], true) || $db->table('role_user')->where('role_id', $id)->countAllResults()) {
            return redirect()->to(site_url('roles'))->with('error', 'No se puede eliminar un rol del sistema o asignado a usuarios.');
        }
        $access = new AccessAssignments();
        $access->transaction(function () use ($access, $id, $model) {
            $access->replace('permission_role', 'role_id', (int) $id, 'permission_id', []);
            $access->replace('role_menu', 'role_id', (int) $id, 'menu_item_id', []);
            if (!$model->delete($id)) { throw new \RuntimeException('Role delete failed'); }
        });
        return redirect()->to(site_url('roles'))->with('success', 'Rol eliminado.');
    }
}
