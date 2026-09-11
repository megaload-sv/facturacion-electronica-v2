# Revisión de seguridad y publicación en HostGator/cPanel

Fecha: 10 de septiembre de 2026 (El Salvador).

**Estado: endurecimiento local, NO desplegado. No constituye una certificación de seguridad ni una auditoría completa de infraestructura.** No se modificaron usuarios, permisos, facturas, secretos ni archivos del servidor. No se hicieron intentos de acceso con contraseñas ni envíos a Hacienda.

## Hallazgos

| Prioridad | Evidencia | Tratamiento |
| --- | --- | --- |
| Urgente | GET público `/login` devolvió 200 e incluyó `debugbar_loader`, Kint y comentarios DEBUG-VIEW. | Eliminados filtros y listeners de toolbar. Publicar con entorno de aplicación production, conservando explícitamente el ambiente fiscal. No se consultaron los datos internos de la barra. |
| Alta | Cookie pública `ci_session` tenía HttpOnly y SameSite=Lax, pero no Secure. No se observaron X-Frame-Options, CSP ni X-Content-Type-Options en esa respuesta. | Cookies Secure y HTTPS obligatorios en production, cabeceras privadas, no-store y protección contra enmarcado. |
| Alta | Autenticación sin límite de intentos ni regeneración explícita de sesión. | Límites por cuenta e IP; ID regenerado al autenticar; 30 minutos de inactividad y 8 horas absolutas. Cuenta eliminada o contraseña cambiada revoca la sesión. |
| Alta | El filtro solo comprobaba logged_in. Ocultar elementos del menú no protegía las rutas administrativas. | Permisos comprobados en la base de datos en cada petición; denegación predeterminada. Administración exclusiva para roles existentes `admin` y `root`. |
| Alta | CSRF desactivado y operaciones de borrado, envío y reenvío accesibles por GET. | CSRF de sesión global, formularios y AJAX adaptados; acciones sensibles solo por POST. |
| Alta | `/info` apuntaba a phpinfo; una redirección desde el controlador base no garantiza detener su ejecución. | Ruta retirada y controlador inutilizado. Rutas de prueba y correo de demostración retiradas. |
| Alta | Composer reportó siete avisos en las versiones bloqueadas. | CodeIgniter 4.7.2 → 4.7.4 y PhpSpreadsheet 5.7.0 → 5.9.0. Composer actualizó también tres dependencias transitivas. Auditoría posterior sin avisos conocidos. |
| Alta | Cargas JSON conservaban nombres enviados por el cliente y no tenían límites propios. | Nombres aleatorios .json, validación de extensión, máximo 100 archivos, 2 MB por archivo y 20 MB por lote. Archivos fuera de public. |
| Alta | Datos de facturas y respuestas externas se insertaban como HTML. | Escape en tablas y mensajes JS, y salidas HTML administrativas. Esto no equivale a cubrir todas las superficies XSS de la aplicación. |
| Alta | Llamadas HTTPS usaban verify=false; dos llamadas ERP estaban codificadas con HTTP. | Verificación de certificados habilitada y esas dos URLs cambiadas a HTTPS. Hay que verificar URLs almacenadas en la tabla seguridad, certificados y acceso al firmador. |
| Alta | La lista de facturas se consultaba desde el navegador directamente al ERP. | Nueva ruta autenticada `/facturas/pendientes/{pagina}/{limite}` con permiso invoices.view, HTTPS y límites. **El ERP externo requiere su propia autenticación; el proxy no cierra su API pública.** |
| Alta | Múltiples respaldos de producción están versionados en data/*.sql. | Regla para no añadir nuevos respaldos y bloqueo Apache de la raíz privada. No se borraron archivos ni se reescribió el historial. No se ha probado que esos respaldos estuvieran accesibles por Internet. |
| Media | Valores de importaciones podían interpretarse como fórmulas al exportar. | Texto explícito en columnas XLSX con datos del emisor y neutralización de prefijos de fórmula en CSV nuevos. Los CSV previamente generados deben revisarse o regenerarse. |

No se atribuye explotación a ninguno de estos hallazgos. La copia local y lo desplegado pueden diferir. HEAD /login devolvió 404, por lo que la comprobación válida del login se hizo con GET.

## Permisos de aplicación

El administrador mantiene la gestión de usuarios, roles, permisos y menús. Los nombres `admin` y `root` proceden de los roles existentes en los respaldos del proyecto; confirmar que existen en la base activa antes del cambio. No se asignó administración automáticamente a ninguna cuenta.

| Nombre exacto | Acceso |
| --- | --- |
| invoices.view | Dashboard, facturas, reportes y descargas |
| invoices.process | Procesar/enviar un DTE |
| invoices.resend | Reprocesar/reenvíar DTE y correos |
| invoices.invalidate | Consultar datos para invalidación e invalidar DTE |
| imports.manage | Cargar, consultar, exportar y eliminar grupos JSON |

Los permisos se pueden asignar directamente a un usuario o a su rol desde los módulos existentes. Los permisos antiguos create_post/edit_post/delete_post/view_post no conceden acceso fiscal. El permiso interno security.manage no se puede delegar a usuarios ordinarios: exige admin/root.

El seeder crea solamente el catálogo, sin asignar permisos:

```sh
php spark db:seed SecurityPermissionsSeeder
```

Ejecutarlo una sola vez en la base correcta durante mantenimiento, después de un respaldo externo. Es idempotente en ejecución secuencial. Si cPanel no ofrece Terminal/SSH, crear los cinco permisos desde el formulario de permisos con una cuenta administradora. Asignar invoices.view además de los permisos de operación que correspondan. Un usuario sin permisos no podrá abrir el dashboard aunque su contraseña sea correcta.

Los permisos son globales dentro de esta instalación: **no implementan aislamiento por empresa, sucursal o propietario de documento**. Si hay varias empresas o usuarios que deban ver únicamente sus propias facturas, definir ese alcance antes de habilitarles acceso.

## Publicación controlada en cPanel

1. Conservar una copia privada del código desplegado, configuración y base de datos para recuperación. No poner respaldos, repositorios ni ZIP de despliegue dentro del directorio público. Restringir temporalmente el acceso público mientras se realiza el cambio.
2. En Domains, revisar el Document Root del subdominio. Debe ser la carpeta **public/** del proyecto. app/, vendor/, writable/, data/, .git y .env deben quedar fuera de cualquier raíz pública de cualquier dominio. El .htaccess de la raíz niega acceso; public/.htaccess permite los recursos públicos y niega archivos sensibles y ejecución de PHP adicional. Si se usa otra distribución de carpetas, adaptarla antes de subir los archivos. No copiar el .htaccess privado sobre el público. [Documentación de cPanel](https://docs.cpanel.net/cpanel/domains/domains/manage-the-domain/).
3. Confirmar certificado válido y activar Force HTTPS Redirect para el subdominio. Si la opción no está disponible, solicitar el ajuste a HostGator. [Documentación de cPanel](https://docs.cpanel.net/cpanel/domains/redirects/).
4. Publicar el código y composer.lock. Instalar dependencias con `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`. Verificar compatibilidad PHP y extensiones del hosting. Si no hay Composer, preparar vendor con ese mismo lock en un entorno compatible y subirlo por un canal seguro. No ejecutar composer update en producción.
5. Configurar el .env privado con estos valores, conservando las credenciales existentes sin exponerlas:

```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://facturador.grupomegaload.com/'
app.forceGlobalSecureRequests = true
cookie.secure = true
cookie.httponly = true
cookie.samesite = Lax
security.csrfProtection = session
security.regenerate = false
cache.handler = file
cache.backupHandler = file

# Elegir conscientemente uno: development, testing o production.
# development => target 1 / ambiente 00
# testing     => target 2 / ambiente 00
# production  => target 0 / ambiente 01 (emisión real)
dte.environment = development
```

**El valor fiscal mostrado es un ejemplo para pruebas, no una instrucción para cambiar el ambiente activo.** Verificar previamente qué registro target/ambiente se usa actualmente y conservar ese valor. Antes, CI_ENVIRONMENT controlaba simultáneamente depuración y selección fiscal. Ahora, con aplicación production, omitir dte.environment bloquea la operación fiscal. El envío automático de correo también utiliza el ambiente fiscal. Esta separación exige publicar Config/Dte.php, SeguridadModel.php y los demás cambios juntos.

6. Comprobar las URLs de la tabla seguridad: ERP, autenticación de Hacienda, recepción, actualización del ERP y firmador. No enviar contraseñas, tokens, firmas o facturas por HTTP a través de Internet. El proxy nuevo exige HTTPS. Si aparece un fallo de certificado, corregir el certificado/cadena CA; no restaurar verify=false. Revisar por separado SMTP/TLS. No se realizaron llamadas reales a esas integraciones durante esta revisión.
7. Crear el catálogo de permisos y asignar únicamente los necesarios. Confirmar una cuenta admin/root antes de habilitar el acceso. Las sesiones anteriores expirarán por no tener los nuevos metadatos: los usuarios deben iniciar sesión de nuevo.
8. writable/cache y writable/session deben ser privados y escribibles por PHP. No aplicar permisos 777. El límite de login utiliza el caché del servidor; no usar el backend dummy. Si hay múltiples servidores se necesita un almacenamiento compartido y limitación adicional en el perímetro. El limitador del framework no sustituye controles de concurrencia/antiabuso del servidor.
9. Revisar errores PHP con display_errors desactivado. Conservar logs privados con retención y acceso restringidos; no exponer datos de facturas o credenciales al navegador. La eliminación de toolbar también retira su endpoint de consulta.

## Verificación antes de habilitar el sitio

- Login responde 200 por GET, no incluye debugbar/Kint y emite cookie Secure, HttpOnly y SameSite. HTTP redirige a HTTPS.
- Sin sesión, administración, descargas y reportes redirigen al login; /info y rutas de prueba no ejecutan controladores.
- Un usuario ordinario recibe 403 en administración y en cada acción no concedida; quitar un permiso surte efecto en la petición siguiente.
- POST sin CSRF o con token incorrecto se rechaza. Formularios válidos y AJAX siguen funcionando, incluso con dos pestañas abiertas.
- GET nunca borra, envía, reenvía, invalida ni cierra sesión. Comprobar botones con cuentas de prueba.
- Validar roles, login, cierre de sesión, expiración y recuperación de acceso con datos de prueba. Las contraseñas nuevas requieren 15–72 caracteres; no se cambió ninguna contraseña existente.
- /data/, /.git/, /.env y /writable/ no permiten lectura/listado ni ejecución. Revisar también dominios principales o alias que pudieran exponer las carpetas privadas.
- Probar facturas, PDF, importación y correo en ambiente fiscal de pruebas. Cualquier emisión o invalidación real requiere revisión funcional deliberada.

## Trabajo pendiente para cerrar la revisión integral

1. **Producción:** no se tiene acceso autenticado a cPanel; falta desplegar y repetir las verificaciones anteriores. No se ha inspeccionado configuración Apache, PHP, aislamiento de cuentas, firewall, TLS completo ni permisos reales del servidor.
2. **Segundo factor:** todavía no hay MFA en el login de la aplicación. Definir TOTP/WebAuthn, recuperación y obligatoriedad, especialmente para administradores. Activar el segundo factor de cPanel es independiente.
3. **ERP y firmador:** auditar autenticación/autorización de sus APIs, autenticación de servicio, transporte, acceso por IP/red privada y firma; no están protegidos automáticamente por esta aplicación.
4. **Datos y secretos:** sacar respaldos del repositorio mediante un procedimiento de conservación y saneamiento del historial. Revisar quién tuvo acceso al repositorio/toolbar/logs; rotar claves potencialmente expuestas de base de datos, SMTP, Hacienda y firma en un orden que permita continuidad. No rotar ni borrar evidencia a ciegas.
5. **Autorización por objeto:** precisar si se requiere separar empresas, sucursales y documentos entre usuarios. El control actual es por acción, no por tenant.
6. **Validación y salidas restantes:** revisión completa de importaciones, PDF, nombres derivados de DTE, respuestas de error, consultas y cargas grandes; inventario y actualización de JavaScript servido desde public/plugins y CDN. La auditoría Composer solo cubre PHP. La CSP añadida limita enmarcado/formularios/objetos, pero todavía no restringe script-src con nonces.
7. **Trazabilidad y recuperación:** registro de quién procesa/invalida/cambia permisos, alertas de acceso, retención, respaldos cifrados fuera del hosting y prueba de restauración. Revisar protección contra doble envío y concurrencia fiscal.

Estos pendientes impiden afirmar que todo el sitio está asegurado o libre de riesgos.

## Evidencia técnica y fuentes

Pruebas automáticas aisladas en SQLite de memoria; sin uso de datos productivos. Incluyen permisos directos/por rol, denegación administrativa, revocación, expiración, CSRF, login válido, respuesta de limitación, ausencia de acciones destructivas GET y separación fiscal. Se verificó sintaxis PHP y de los tres scripts de facturas. No se hicieron pruebas de interfaz con sesión real ni pruebas fiscales de extremo a extremo.

Avisos iniciales reportados por Composer:

- CodeIgniter: [nombres de archivo](https://github.com/advisories/GHSA-hhmc-q9hp-r662), [validación de archivos](https://github.com/advisories/GHSA-mmj4-63m4-r6h5), [deleteBatch](https://github.com/advisories/GHSA-c9w5-rwh3-7pm9), [cabeceras HTTPS](https://github.com/advisories/GHSA-7wmf-pw8j-mc78).
- PhpSpreadsheet: [XLS/OLE](https://github.com/advisories/GHSA-xh5m-36r6-47m3), [Gnumeric](https://github.com/advisories/GHSA-2mrg-gjxq-2gvr), [WEBSERVICE](https://github.com/advisories/GHSA-6hq5-7373-42rg).

Una auditoría sin avisos conocidos no garantiza ausencia de vulnerabilidades.
