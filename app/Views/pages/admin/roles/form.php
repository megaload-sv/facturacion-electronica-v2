<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Roles y permisos | Megaload<?= $this->endSection() ?>
<?= $this->section('content-header') ?><div class="ml-page-heading"><div><span class="ml-eyebrow">ADMINISTRACIÓN / ROLES</span><h1><?= $role ? 'Editar rol' : 'Crear rol' ?></h1><p>Defina un perfil de acceso reutilizable para su equipo.</p></div><a class="btn btn-outline-secondary" href="<?= site_url('roles') ?>">Volver a roles</a></div><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= $this->include('pages/admin/access/nav') ?>
<form method="post" action="<?= site_url($role ? 'roles/update/' . $role['id'] : 'roles/store') ?>" class="access-form">
<?= csrf_field() ?>
<div class="access-grid"><section class="ml-panel"><span class="ml-eyebrow">PERFIL DEL EQUIPO</span><h2>Datos y permisos del rol</h2>
<div class="form-group"><label for="role_name">Nombre del rol</label><input class="form-control" id="role_name" name="role_name" minlength="3" maxlength="50" pattern="[A-Za-z0-9_-]+" value="<?= esc($input['role_name'] ?? $role['role_name'] ?? '') ?>" required <?= in_array($role['role_name'] ?? '', ['admin', 'root'], true) ? 'readonly' : '' ?>><small class="text-muted">Letras sin tildes, números, guiones o guiones bajos.</small></div>
<div class="form-group"><label for="description">Descripción</label><textarea class="form-control" name="description" id="description" maxlength="255" rows="2"><?= esc($input['description'] ?? $role['description'] ?? '') ?></textarea></div>
<hr><h2>Permisos del rol</h2>
<?php if (in_array($role['role_name'] ?? '', ['admin', 'root'], true)): ?><p class="alert alert-info">Este rol del sistema tiene acceso completo, independientemente de esta selección.</p><?php endif ?>
<?= $this->include('pages/admin/access/permissions') ?>
</section><aside class="ml-panel access-summary"><span class="ml-eyebrow">ACCESO COMPARTIDO</span><h2>Un cambio para todo el equipo</h2><p class="text-muted">Los permisos seleccionados se aplican a todos los usuarios de este rol al guardar.</p><p class="text-muted">Un usuario puede conservar otros accesos a través de sus demás roles o permisos adicionales.</p></aside></div>
<div class="access-actions"><a class="btn btn-outline-secondary" href="<?= site_url('roles') ?>">Cancelar</a><button class="btn btn-primary" type="submit">Guardar rol y permisos</button></div></form>
<?= $this->endSection() ?>
