<link rel="stylesheet" href="<?= base_url('css/access.css') ?>?v=1">
<?php $accessSection = explode('/', trim(uri_string(), '/'))[0]; ?>
<nav class="access-tabs" aria-label="Administración de accesos">
    <a href="<?= site_url('users') ?>" <?= in_array($accessSection, ['users', 'permission_users'], true) ? 'aria-current="page"' : '' ?>><i class="fas fa-user-friends" aria-hidden="true"></i> Usuarios</a>
    <a href="<?= site_url('roles') ?>" <?= in_array($accessSection, ['roles', 'permission_roles'], true) ? 'aria-current="page"' : '' ?>><i class="fas fa-shield-alt" aria-hidden="true"></i> Roles y permisos</a>
</nav>
<?php if (!empty($errors)): ?>
<div class="alert alert-danger" role="alert" tabindex="-1" id="access-errors"><strong>Revise los siguientes datos</strong><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach ?></ul></div>
<?php endif ?>
