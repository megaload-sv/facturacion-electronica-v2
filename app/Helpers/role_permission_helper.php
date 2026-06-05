<?php


if (!function_exists('hasRoleOrPermission')) {
    function hasRoleOrPermission($permissionName, $roles)
    {
        // Lógica para verificar si el usuario tiene el rol o permiso adecuado
        return in_array($permissionName, $roles);  // Modificar según tu lógica
    }
}
