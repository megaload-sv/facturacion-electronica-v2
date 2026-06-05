<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\RoleUserModel; // Modelo de la tabla que relaciona roles y usuarios
use App\Models\RoleModel;
use CodeIgniter\Controller;

class RolesUsersController extends BaseController
{
    public function viewRoles($userId)
    {
        $userModel = new UserModel();
        $roleUserModel = new RoleUserModel(); // Relación roles-usuarios
        $roleModel = new RoleModel();

        // Obtener la información del usuario
        $user = $userModel->find($userId);

        // Obtener los roles asignados a este usuario
        $assignedRoles = $roleUserModel
            ->select('roles.*')
            ->join('roles', 'roles.id = role_user.role_id')
            ->where('role_user.user_id', $userId)
            ->findAll();

        // Pasar los datos a la vista
        return $this->render('pages/admin/roles_users/index', [
            'user' => $user,
            'assignedRoles' => $assignedRoles
        ]);
    }
    // Método para mostrar los roles asignados a un usuario y permitir gestionarlos
    public function manageRoles($userId)
    {
        $userModel = new UserModel();
        $roleUserModel = new RoleUserModel();
        $roleModel = new RoleModel();

        // Obtener la información del usuario
        $user = $userModel->find($userId);

        // Obtener los roles asignados al usuario
        $assignedRoles = $roleUserModel
            ->select('roles.*')
            ->join('roles', 'roles.id = role_user.role_id')
            ->where('role_user.user_id', $userId)
            ->findAll();

        // Obtener todos los roles para asignación
        $roles = $roleModel->findAll();

        // Pasar los datos a la vista
        return $this->render('pages/admin/roles_users/manage', [
            'user' => $user,
            'assignedRoles' => $assignedRoles,
            'roles' => $roles
        ]);
    }

    // Método para asignar un rol a un usuario
    public function assignRole()
    {
        $roleUserModel = new RoleUserModel();

        // Obtener los datos del formulario
        $data = [
            'user_id' => $this->request->getPost('user_id'),
            'role_id' => $this->request->getPost('role_id')
        ];

        // Insertar la nueva asignación de rol
        $roleUserModel->save($data);

        // Redireccionar a la gestión de roles del usuario
        return $this->render('/users/roles/manage/' . $data['user_id']);
    }

    // Método para eliminar un rol asignado a un usuario
    public function removeRole($userId, $roleId)
    {
        $roleUserModel = new RoleUserModel();

        // Eliminar el rol asignado al usuario
        $roleUserModel->where('user_id', $userId)->where('role_id', $roleId)->delete();

        // Redireccionar de vuelta a la gestión de roles
       return redirect()->to('/users/roles/manage/' . $userId);
    }
}