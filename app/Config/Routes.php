<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->group('', ['filter' => 'auth'], function ($routes) {
    // esto es para las rutas del dashboard
    $routes->get('/dashboard', 'DashboardController::index');


    //para las facturas
    $routes->get('facturas/', 'FacturasController::index');
    $routes->get('facturas/facturasToMH', 'FacturasController::getfacturas');
    $routes->get('facturas/procesarDTE/(:segment)','FacturasController::procesarDTE/$1');
    $routes->get('facturas/procesarDTE/(:segment)/(:segment)', 'FacturasController::procesarDTE/$1/$2');
    $routes->get('facturas-procesadas', 'FacturasController::facturas_procesadas');
    $routes->get('facturas/sellos', 'FacturasController::facturas_procesadas_data');
    $routes->get('facturas/mostrarErrorMH/(:any)', 'FacturasController::mostrarErrorMH/$1');
    $routes->get('procesadas-archivadas', 'FacturasController::procesadas_archivadas');
    $routes->get('facturas/sellos-archivo/', 'FacturasController::facturas_archivadas_data');
    $routes->get('facturas/reenviar-dte/(:segment)','FacturasController::reenviardte/$1');
    $routes->get('facturas/descargar-json/(:segment)', 'FacturasController::descargarJSON/$1');
    $routes->get('facturas/generar-pdf/(:segment)', 'FacturasController::generarPDF/$1');
    $routes->get('facturas/generar-pdf-invalidacion/(:segment)', 'FacturasController::generarPDFInvalidacion/$1');
    $routes->get('facturas/total-sellos-archivo', 'FacturasController::facturas_archivadas_total');
    $routes->get('facturas/dte-correo-data/(:segment)', 'FacturasController::factura_correo_receptor/$1');
    $routes->post('facturas/dte-correo-data-process/(:segment)', 'FacturasController::factura_reenviar_correo/$1');

//para los cruds
    $routes->get('/permissions', 'PermissionsController::index');
    $routes->get('permissions/create', 'PermissionsController::create');
    $routes->post('permissions/store', 'PermissionsController::store');
    $routes->get('permissions/edit/(:segment)', 'PermissionsController::edit/$1');
    $routes->post('permissions/update/(:segment)', 'PermissionsController::update/$1');
    $routes->get('permissions/delete/(:segment)', 'PermissionsController::delete/$1');

    $routes->get('roles', 'RolesController::index');
    $routes->get('roles/create', 'RolesController::create');
    $routes->post('roles/store', 'RolesController::store');
    $routes->get('roles/edit/(:segment)', 'RolesController::edit/$1');
    $routes->post('roles/update/(:segment)', 'RolesController::update/$1');
    $routes->get('roles/delete/(:segment)', 'RolesController::delete/$1');

    $routes->get('permission_roles', 'PermissionRolesController::index');
    $routes->get('permission_roles/create', 'PermissionRolesController::create');
    $routes->post('permission_roles/store', 'PermissionRolesController::store');
    $routes->get('permission_roles/delete/(:segment)', 'PermissionRolesController::delete/$1');

// Ruta para ver los permisos asignados a un rol
    $routes->get('permission_roles/view/(:segment)', 'PermissionRolesController::view/$1');
// Ruta para eliminar la asignación de un permiso a un rol
    $routes->get('permission_roles/delete/(:segment)/(:segment)', 'PermissionRolesController::delete_role_permission/$1/$2');

    $routes->get('permission_users', 'PermissionUsersController::index');
    $routes->get('permission_users/create', 'PermissionUsersController::create');
    $routes->get('permission_users/edit/(:segment)/(:segment)', 'PermissionUsersController::edit/$1/$2');
    $routes->post('permission_users/store', 'PermissionUsersController::store');
    $routes->get('permission_users/delete/(:segment)', 'PermissionUsersController::delete/$1');

// Rutas para la gestión de usuarios
    $routes->get('users', 'UsersController::index');
    $routes->get('users/create', 'UsersController::create');
    $routes->post('users/store', 'UsersController::store');
    $routes->get('users/edit/(:segment)', 'UsersController::edit/$1');
    $routes->post('users/update/(:segment)', 'UsersController::update/$1');
    $routes->get('users/delete/(:segment)', 'UsersController::delete/$1');
// Ruta para ver los permisos asignados a un usuario
    $routes->get('users/permissions/(:segment)', 'PermissionUsersController::viewPermissions/$1');
// Ruta para ver los roles asignados a un usuario específico
    $routes->get('users/roles/(:segment)', 'RolesUsersController::viewRoles/$1');
// Ruta para gestionar los roles asignados a un usuario
    $routes->get('users/roles/manage/(:segment)', 'RolesUsersController::manageRoles/$1');
// Ruta para asignar un rol a un usuario
    $routes->post('users/roles/assign', 'RolesUsersController::assignRole');
// Ruta para eliminar un rol asignado a un usuario
    $routes->get('users/roles/remove/(:segment)/(:segment)', 'RolesUsersController::removeRole/$1/$2');

    $routes->get('getmenu', 'MenuController::getMenu');
    $routes->get('/menu', 'MenuController::index');
    $routes->get('/menu/create', 'MenuController::create');
    $routes->post('/menu/store', 'MenuController::store');
    $routes->get('/menu/edit/(:num)', 'MenuController::edit/$1');
    $routes->post('/menu/update/(:num)', 'MenuController::update/$1');
    $routes->get('/menu/delete/(:num)', 'MenuController::delete/$1');

    $routes->get('/permission_menu', 'PermissionMenuController::index');
    $routes->get('/permission_menu/edit/(:num)', 'PermissionMenuController::edit/$1');
    $routes->post('/permission_menu/store', 'PermissionMenuController::store');
    $routes->get('/permission_menu/delete/(:num)/(:num)', 'PermissionMenuController::delete/$1/$2');

    $routes->get('/role_menu', 'RoleMenuController::index');
    $routes->get('role_menu/edit/(:num)', 'RoleMenuController::edit/$1');
    $routes->post('/role_menu/store', 'RoleMenuController::store');
    $routes->get('/role_menu/delete/(:num)/(:num)', 'RoleMenuController::delete/$1/$2');

    $routes->get('enviar-correo', 'EmailController::enviarCorreo');

    $routes->get('/dte-invalidar/(:segment)', 'DTEController::getInvalidarDTEInfo/$1');
    $routes->post('/dte-invalidar/', 'DTEController::procesarInvalidarDTE');

// rutas para las pruebas
    $routes->get('dte/generar', 'DTEController::generarConsumidorFinal');
    $routes->get('dte/generarCreditoFiscal', 'DTEController::generarCreditoFiscal');
    $routes->get('dte/generarExportacion', 'DTEController::generarExportacion');
    $routes->get('dte/generarSujetoExcluido', 'DTEController::generarSujetoExcluido');
    $routes->get('dte/generarNotaCredito', 'DTEController::generarNotaCredito');
    $routes->get('dte/generarNotaDebito', 'DTEController::generarNotaDebito');

    $routes->get('dte/test', 'DTEController::vistaTest');

    $routes->get('procesar-json', 'ProcesarJsonController::index');
    $routes->post('procesar-json/procesar', 'ProcesarJsonController::procesar');
    $routes->get('procesar-json/descargar-csv/(:num)', 'ProcesarJsonController::descargarCsv/$1');
    $routes->get('procesar-json/descargar-xls/(:num)', 'ProcesarJsonController::descargarExcelPhpSpreadsheet/$1');
    $routes->post('procesar-json/eliminar/(:num)', 'ProcesarJsonController::eliminarGrupo/$1');

    // Reportes
    $routes->get('reportes/declaraciones', 'ReportesController::declaraciones');
    $routes->get('reportes/declaraciones/data', 'ReportesController::declaracionesData');

});

// esto es para las rutas del login
$routes->get('/', 'LoginController::index');
$routes->get('/login', 'LoginController::index');
$routes->post('login/autenticar', 'Login::autenticar');
$routes->get('/', 'LoginController::index');
$routes->post('/login/authenticate', 'LoginController::authenticate');
$routes->get('/logout', 'LoginController::logout');


$routes->get('/info', 'InfoController::index');
