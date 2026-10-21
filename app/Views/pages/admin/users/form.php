<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= $user ? 'Editar usuario' : 'Nuevo usuario' ?> | Megaload<?= $this->endSection() ?>
<?= $this->section('content-header') ?>
<div class="ml-page-heading"><div><span class="ml-eyebrow">ADMINISTRACIÓN / USUARIOS</span><h1><?= $user ? 'Editar usuario' : 'Crear usuario' ?></h1><p>Una cuenta, sus roles y todos sus accesos en un mismo lugar.</p></div><a class="btn btn-outline-secondary" href="<?= site_url('users') ?>">Volver a usuarios</a></div>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= $this->include('pages/admin/access/nav') ?>
<form method="post" action="<?= site_url($user ? 'users/update/' . $user['id'] : 'users/store') ?>" class="access-form">
<?= csrf_field() ?>
<div class="access-grid"><div>
<section class="ml-panel mb-4"><span class="ml-eyebrow">01 / IDENTIDAD</span><h2>Datos de la cuenta</h2><p class="text-muted">La información que utilizará para ingresar a la plataforma.</p>
<div class="form-group"><label for="user_name">Nombre de usuario</label><input class="form-control" id="user_name" name="user_name" value="<?= esc($input['user_name'] ?? $user['username'] ?? '') ?>" minlength="3" maxlength="100" autocomplete="username" required></div>
<div class="form-group"><label for="email">Correo electrónico</label><input class="form-control" id="email" name="email" type="email" maxlength="254" value="<?= esc($input['email'] ?? $user['email'] ?? '') ?>" autocomplete="email" required></div>
<div class="access-fields"><div class="form-group"><label for="password"><?= $user ? 'Nueva contraseña (opcional)' : 'Contraseña' ?></label><input class="form-control" type="password" name="password" id="password" minlength="15" maxlength="72" autocomplete="new-password" aria-describedby="password-help" <?= $user ? '' : 'required' ?>></div><div class="form-group"><label for="password_confirm">Confirmar contraseña</label><input class="form-control" type="password" name="password_confirm" id="password_confirm" autocomplete="new-password" <?= $user ? '' : 'required' ?>></div></div>
<small id="password-help" class="text-muted">Use de 15 a 72 caracteres. <?= $user ? 'Deje ambos campos vacíos para conservar la contraseña actual.' : 'Una frase larga es más fácil de recordar.' ?></small>
</section>
<section class="ml-panel mb-4"><span class="ml-eyebrow">02 / PERFIL DE ACCESO</span><h2>Roles del usuario</h2><p class="text-muted">Seleccione al menos uno. Sus permisos se heredan automáticamente.</p>
<div class="access-options"><?php foreach ($roles as $role): ?><label class="access-option"><input type="checkbox" name="role_ids[]" value="<?= (int) $role['id'] ?>" data-admin="<?= in_array($role['role_name'], ['admin', 'root'], true) ? '1' : '0' ?>" <?= in_array((int) $role['id'], $selectedRoles, true) ? 'checked' : '' ?>><span><strong><?= esc($role['role_name']) ?></strong><small><?= esc($role['description'] ?? '') ?><?= in_array($role['role_name'], ['admin', 'root'], true) ? ' · Acceso completo, incluida administración' : '' ?></small></span></label><?php endforeach ?></div>
<?php if (!$roles): ?><p>No hay roles disponibles. <a href="<?= site_url('roles/create') ?>">Cree el primer rol</a>.</p><?php endif ?>
</section>
<section class="ml-panel"><span class="ml-eyebrow">03 / EXCEPCIONES</span><h2>Permisos adicionales</h2><p class="text-muted">Se suman a los roles y permanecen aunque cambie el rol. Desmarcar un permiso adicional no revoca el acceso heredado.</p>
<?= $this->include('pages/admin/access/permissions') ?>
</section></div>
<aside class="ml-panel access-summary"><span class="ml-eyebrow">VISTA PREVIA</span><h2>Acceso resultante</h2><p class="text-muted">Roles + permisos adicionales</p><div id="access-summary" aria-live="polite"></div><noscript>Active JavaScript para visualizar el resumen. Los accesos se validan al guardar.</noscript><hr><p class="text-muted">Los roles <strong>admin</strong> y <strong>root</strong> tienen acceso completo. La administración no se concede mediante un permiso individual.</p></aside>
</div>
<div class="access-actions"><a href="<?= site_url('users') ?>" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-primary" type="submit"><i class="fas fa-check" aria-hidden="true"></i> <?= $user ? 'Guardar cambios' : 'Crear usuario' ?></button></div>
</form>
<script type="application/json" id="access-role-permissions"><?= json_encode($rolePermissions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?= $this->endSection() ?>
<?= $this->section('js-page') ?><script src="<?= base_url('js/access.js') ?>"></script><?= $this->endSection() ?>
