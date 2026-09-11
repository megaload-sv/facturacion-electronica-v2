<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setAutoRoute(false);
$routes->group('', [], function ($routes) {
    // esto es para las rutas del dashboard
    $routes->get('/dashboard', 'DashboardController::index', ['filter' => 'permission:invoices.view']);


    //para las facturas
    $routes->get('facturas/', 'FacturasController::index', ['filter' => 'permission:invoices.view']);
    $routes->get('facturas/pendientes/(:num)/(:num)', 'FacturasController::pendientes/$1/$2', ['filter' => 'permission:invoices.view']);
    $routes->post('facturas/procesarDTE/(:segment)','FacturasController::procesarDTE/$1', ['filter' => 'permission:invoices.process']);
    $routes->post('facturas/procesarDTE/(:segment)/(:segment)', 'FacturasController::procesarDTE/$1/$2', ['filter' => 'permission:invoices.process']);
    $routes->get('facturas-procesadas', 'FacturasController::facturas_procesadas', ['filter' => 'permission:invoices.view']);
    $routes->get('facturas/sellos', 'FacturasController::facturas_procesadas_data', ['filter' => 'permission:invoices.view']);
    $routes->get('facturas/mostrarErrorMH/(:any)', 'FacturasController::mostrarErrorMH/$1', ['filter' => 'permission:invoices.view']);
    $routes->get('procesadas-archivadas', 'FacturasController::procesadas_archivadas', ['filter' => 'permission:invoices.view']);
    $routes->get('facturas/sellos-archivo/', 'FacturasController::facturas_archivadas_data', ['filter' => 'permission:invoices.view']);
    $routes->post('facturas/reenviar-dte/(:segment)','FacturasController::reenviardte/$1', ['filter' => 'permission:invoices.resend']);
    $routes->get('facturas/descargar-json/(:segment)', 'FacturasController::descargarJSON/$1', ['filter' => 'permission:invoices.view']);
    $routes->get('facturas/generar-pdf/(:segment)', 'FacturasController::generarPDF/$1', ['filter' => 'permission:invoices.view']);
    $routes->get('facturas/generar-pdf-invalidacion/(:segment)', 'FacturasController::generarPDFInvalidacion/$1', ['filter' => 'permission:invoices.view']);
    $routes->get('facturas/total-sellos-archivo', 'FacturasController::facturas_archivadas_total', ['filter' => 'permission:invoices.view']);
    $routes->get('facturas/dte-correo-data/(:segment)', 'FacturasController::factura_correo_receptor/$1', ['filter' => 'permission:invoices.view']);
    $routes->post('facturas/dte-correo-data-process/(:segment)', 'FacturasController::factura_reenviar_correo/$1', ['filter' => 'permission:invoices.resend']);

//para los cruds
    $routes->get('/permissions', 'PermissionsController::index', ['filter' => 'permission:security.manage']);
    $routes->get('permissions/create', 'PermissionsController::create', ['filter' => 'permission:security.manage']);
    $routes->post('permissions/store', 'PermissionsController::store', ['filter' => 'permission:security.manage']);
    $routes->get('permissions/edit/(:segment)', 'PermissionsController::edit/$1', ['filter' => 'permission:security.manage']);
    $routes->post('permissions/update/(:segment)', 'PermissionsController::update/$1', ['filter' => 'permission:security.manage']);
    $routes->post('permissions/delete/(:segment)', 'PermissionsController::delete/$1', ['filter' => 'permission:security.manage']);

    $routes->get('roles', 'RolesController::index', ['filter' => 'permission:security.manage']);
    $routes->get('roles/create', 'RolesController::create', ['filter' => 'permission:security.manage']);
    $routes->post('roles/store', 'RolesController::store', ['filter' => 'permission:security.manage']);
    $routes->get('roles/edit/(:segment)', 'RolesController::edit/$1', ['filter' => 'permission:security.manage']);
    $routes->post('roles/update/(:segment)', 'RolesController::update/$1', ['filter' => 'permission:security.manage']);
    $routes->post('roles/delete/(:segment)', 'RolesController::delete/$1', ['filter' => 'permission:security.manage']);

    $routes->get('permission_roles', 'PermissionRolesController::index', ['filter' => 'permission:security.manage']);
    $routes->get('permission_roles/create', 'PermissionRolesController::create', ['filter' => 'permission:security.manage']);
    $routes->post('permission_roles/store', 'PermissionRolesController::store', ['filter' => 'permission:security.manage']);
    $routes->post('permission_roles/delete/(:segment)', 'PermissionRolesController::delete/$1', ['filter' => 'permission:security.manage']);

// Ruta para ver los permisos asignados a un rol
    $routes->get('permission_roles/view/(:segment)', 'PermissionRolesController::view/$1', ['filter' => 'permission:security.manage']);
// Ruta para eliminar la asignación de un permiso a un rol
    $routes->post('permission_roles/delete/(:segment)/(:segment)', 'PermissionRolesController::delete_role_permission/$1/$2', ['filter' => 'permission:security.manage']);

    $routes->get('permission_users', 'PermissionUsersController::index', ['filter' => 'permission:security.manage']);
    $routes->get('permission_users/create', 'PermissionUsersController::create', ['filter' => 'permission:security.manage']);
    $routes->get('permission_users/edit/(:segment)/(:segment)', 'PermissionUsersController::edit/$1/$2', ['filter' => 'permission:security.manage']);
    $routes->post('permission_users/update/(:num)/(:num)', 'PermissionUsersController::update/$1/$2', ['filter' => 'permission:security.manage']);
    $routes->post('permission_users/store', 'PermissionUsersController::store', ['filter' => 'permission:security.manage']);
    $routes->post('permission_users/delete/(:num)/(:num)', 'PermissionUsersController::delete/$1/$2', ['filter' => 'permission:security.manage']);

// Rutas para la gestión de usuarios
    $routes->get('users', 'UsersController::index', ['filter' => 'permission:security.manage']);
    $routes->get('users/create', 'UsersController::create', ['filter' => 'permission:security.manage']);
    $routes->post('users/store', 'UsersController::store', ['filter' => 'permission:security.manage']);
    $routes->get('users/edit/(:segment)', 'UsersController::edit/$1', ['filter' => 'permission:security.manage']);
    $routes->post('users/update/(:segment)', 'UsersController::update/$1', ['filter' => 'permission:security.manage']);
    $routes->post('users/delete/(:segment)', 'UsersController::delete/$1', ['filter' => 'permission:security.manage']);
// Ruta para ver los permisos asignados a un usuario
    $routes->get('users/permissions/(:segment)', 'PermissionUsersController::viewPermissions/$1', ['filter' => 'permission:security.manage']);
// Ruta para ver los roles asignados a un usuario específico
    $routes->get('users/roles/(:segment)', 'RolesUsersController::viewRoles/$1', ['filter' => 'permission:security.manage']);
// Ruta para gestionar los roles asignados a un usuario
    $routes->get('users/roles/manage/(:segment)', 'RolesUsersController::manageRoles/$1', ['filter' => 'permission:security.manage']);
// Ruta para asignar un rol a un usuario
    $routes->post('users/roles/assign', 'RolesUsersController::assignRole', ['filter' => 'permission:security.manage']);
// Ruta para eliminar un rol asignado a un usuario
    $routes->post('users/roles/remove/(:segment)/(:segment)', 'RolesUsersController::removeRole/$1/$2', ['filter' => 'permission:security.manage']);

    $routes->get('getmenu', 'MenuController::getMenu');
    $routes->get('/menu', 'MenuController::index', ['filter' => 'permission:security.manage']);
    $routes->get('/menu/create', 'MenuController::create', ['filter' => 'permission:security.manage']);
    $routes->post('/menu/store', 'MenuController::store', ['filter' => 'permission:security.manage']);
    $routes->get('/menu/edit/(:num)', 'MenuController::edit/$1', ['filter' => 'permission:security.manage']);
    $routes->post('/menu/update/(:num)', 'MenuController::update/$1', ['filter' => 'permission:security.manage']);
    $routes->post('/menu/delete/(:num)', 'MenuController::delete/$1', ['filter' => 'permission:security.manage']);

    $routes->get('/permission_menu', 'PermissionMenuController::index', ['filter' => 'permission:security.manage']);
    $routes->get('/permission_menu/edit/(:num)', 'PermissionMenuController::edit/$1', ['filter' => 'permission:security.manage']);
    $routes->post('/permission_menu/store', 'PermissionMenuController::store', ['filter' => 'permission:security.manage']);
    $routes->post('/permission_menu/delete/(:num)/(:num)', 'PermissionMenuController::delete/$1/$2', ['filter' => 'permission:security.manage']);

    $routes->get('/role_menu', 'RoleMenuController::index', ['filter' => 'permission:security.manage']);
    $routes->get('role_menu/edit/(:num)', 'RoleMenuController::edit/$1', ['filter' => 'permission:security.manage']);
    $routes->post('/role_menu/store', 'RoleMenuController::store', ['filter' => 'permission:security.manage']);
    $routes->post('/role_menu/delete/(:num)/(:num)', 'RoleMenuController::delete/$1/$2', ['filter' => 'permission:security.manage']);


    $routes->get('/dte-invalidar/(:segment)', 'DTEController::getInvalidarDTEInfo/$1', ['filter' => 'permission:invoices.invalidate']);
    $routes->post('/dte-invalidar/', 'DTEController::procesarInvalidarDTE', ['filter' => 'permission:invoices.invalidate']);

// rutas para las pruebas


    $routes->get('procesar-json', 'ProcesarJsonController::index', ['filter' => 'permission:imports.manage']);
    $routes->post('procesar-json/procesar', 'ProcesarJsonController::procesar', ['filter' => 'permission:imports.manage']);
    $routes->get('procesar-json/descargar-csv/(:num)', 'ProcesarJsonController::descargarCsv/$1', ['filter' => 'permission:imports.manage']);
    $routes->get('procesar-json/descargar-xls/(:num)', 'ProcesarJsonController::descargarExcelPhpSpreadsheet/$1', ['filter' => 'permission:imports.manage']);
    $routes->post('procesar-json/eliminar/(:num)', 'ProcesarJsonController::eliminarGrupo/$1', ['filter' => 'permission:imports.manage']);

    // Reportes
    $routes->get('reportes/declaraciones', 'ReportesController::declaraciones', ['filter' => 'permission:invoices.view']);
    $routes->get('reportes/declaraciones/data', 'ReportesController::declaracionesData', ['filter' => 'permission:invoices.view']);

});

// esto es para las rutas del login
$routes->get('/', 'LoginController::index');
$routes->get('/login', 'LoginController::index');
$routes->post('/login/authenticate', 'LoginController::authenticate');
$routes->post('/logout', 'LoginController::logout');
