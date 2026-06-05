<?php

namespace App\Controllers;

use App\Models\MenuModel;
use App\Models\PermissionRoleModel;
use App\Models\RoleModel;
use App\Models\PermissionModel;
use CodeIgniter\Controller;

class PermissionRolesController extends BaseController
{

    protected MenuModel $_menuModel;

    public function __construct()
    {
        $this->_menuModel = new MenuModel();
    }


    public function index()
    {



        // Modelo de Permisos y Roles
        $permissionRoleModel = new PermissionRoleModel();
        $roleModel = new RoleModel();
        $permissionModel = new PermissionModel();

        // Obtener todas las asignaciones de permisos a roles
        $permissionRoles = $permissionRoleModel
            ->select('permission_role.role_id, permission_role.permission_id, roles.role_name, permissions.permission_name')
            ->join('roles', 'roles.id = permission_role.role_id')
            ->join('permissions', 'permissions.id = permission_role.permission_id')
            ->findAll();

        // Pasar los datos a la vista
        return $this->render('pages/admin/permission_roles/index', [
            'permissionRoles' => $permissionRoles,
        ]);

    }

    public function create()
    {
        $roleModel = new RoleModel();
        $permissionModel = new PermissionModel();
        $data['roles'] = $roleModel->findAll();
        $data['permissions'] = $permissionModel->findAll();
        return $this->render('pages/admin/permission_roles/create', $data);
    }

    public function store()
    {
        $model = new PermissionRoleModel();
        $model->save([
            'role_id' => $this->request->getPost('role_id'),
            'permission_id' => $this->request->getPost('permission_id'),
        ]);
        return redirect()->to('permission_roles');
    }

    public function delete($id)
    {
        $model = new PermissionRoleModel();
        $model->delete($id);
        return redirect()->to('permission_roles');
    }

    public function view($roleId)
    {
        // Modelo de Roles
        $roleModel = new RoleModel();
        $role = $roleModel->find($roleId);

        // Modelo de Permisos
        $permissionModel = new PermissionModel();

        // Obtener los permisos asignados al rol
        $permissionRoleModel = new PermissionRoleModel();
        $assignedPermissions = $permissionRoleModel
            ->select('permissions.*')
            ->join('permissions', 'permissions.id = permission_role.permission_id')
            ->where('permission_role.role_id', $roleId)
            ->findAll();

        // Pasar los datos a la vista
        return $this->render('pages/admin/permission_roles/view', [
            'role' => $role,
            'assignedPermissions' => $assignedPermissions,
        ]);
    }

    public function delete_role_permission($roleId, $permissionId)
    {
        $model = new PermissionRoleModel();

        // Eliminar la asignación del permiso al rol
        $model->where('role_id', $roleId)->where('permission_id', $permissionId)->delete();

        // Redireccionar de vuelta a la vista del rol
        return redirect()->to("/permission_roles/view/$roleId");
    }
}
