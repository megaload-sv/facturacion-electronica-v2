<?php

namespace App\Controllers;

use App\Models\PermissionUserModel;
use App\Models\UserModel; // Asegúrate de tener un modelo de usuarios
use App\Models\PermissionModel;
use CodeIgniter\Controller;

class PermissionUsersController extends BaseController
{
    public function index()
    {
        // Modelos de Permisos y Usuarios
        $permissionUserModel = new PermissionUserModel();
        $userModel = new UserModel();  // Para información del usuario
        $permissionModel = new PermissionModel();  // Para información de los permisos

        // Obtener todas las asignaciones de permisos a usuarios
        $permissionUsers = $permissionUserModel
            ->select('permission_user.user_id, permission_user.permission_id, users.username, permissions.permission_name')
            ->join('users', 'users.id = permission_user.user_id')  // Join con la tabla de usuarios
            ->join('permissions', 'permissions.id = permission_user.permission_id')  // Join con la tabla de permisos
            ->findAll();

        // Pasar los datos a la vista
        return $this->render('pages/admin/permission_users/index', [
            'permissionUsers' => $permissionUsers
        ]);
    }

    public function create()
    {
        $userModel = new UserModel();
        $permissionModel = new PermissionModel();
        $data['users'] = $userModel->findAll();
        $data['permissions'] = $permissionModel->findAll();
        return $this->render('pages/admin/permission_users/create', $data);
    }

    public function store()
    {
        $model = new PermissionUserModel();
        $model->save([
            'user_id' => $this->request->getPost('user_id'),
            'permission_id' => $this->request->getPost('permission_id'),
        ]);
        return redirect()->to('/permission_users');
    }

    public function delete($id)
    {
        $model = new PermissionUserModel();
        $model->delete($id);
        return redirect()->to('/permission_users');
    }

    public function viewPermissions($userId)
    {
        // Modelos necesarios
        $userModel = new UserModel();
        $permissionUserModel = new PermissionUserModel();
        $permissionModel = new PermissionModel();

        // Obtener el usuario
        $user = $userModel->find($userId);

        // Obtener los permisos asignados al usuario
        $assignedPermissions = $permissionUserModel
            ->select('permissions.*')
            ->join('permissions', 'permissions.id = permission_user.permission_id')
            ->where('permission_user.user_id', $userId)
            ->findAll();

        // Enviar datos a la vista
        return $this->render('pages/admin/permission_users/view', [
            'user' => $user,
            'assignedPermissions' => $assignedPermissions
        ]);
    }
    public function edit($user_id, $permission_id)
    {
        $userModel = new UserModel();
        $permissionModel = new PermissionModel();
        $model = new PermissionUserModel();
        //$data['permission_user'] = $model->find($id);
        $data['users'] = $userModel->findAll();  // Asegúrate de obtener todos los usuarios
        $data['permissions'] = $permissionModel->findAll();

        $data['user_id'] = $user_id;
        $data['permission_id'] = $permission_id;
        return $this->render('pages/admin/permission_users/edit', $data);
    }


}
