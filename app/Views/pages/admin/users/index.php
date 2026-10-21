<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Usuarios y accesos | Megaload<?= $this->endSection() ?>
<?= $this->section('content-header') ?><div class="ml-page-heading"><div><span class="ml-eyebrow">ADMINISTRACIÓN</span><h1>Usuarios y accesos</h1><p>Organice su equipo y controle qué puede hacer cada persona.</p></div><a class="btn btn-primary" href="<?= site_url('users/create') ?>"><i class="fas fa-plus" aria-hidden="true"></i> Crear usuario</a></div><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= $this->include('pages/admin/access/nav') ?>
<section class="ml-panel"><div class="access-list-heading"><div><span class="ml-eyebrow">DIRECTORIO DEL EQUIPO</span><h2>Usuarios <span class="access-count"><?= count($users) ?></span></h2></div></div>
<div class="table-responsive"><table class="table table-bordered table-striped" data-datatable><thead><tr><th scope="col">Usuario</th><th scope="col">Correo electrónico</th><th scope="col">Roles</th><th scope="col" class="text-right" data-table-actions>Acciones</th></tr></thead><tbody>
<?php $byUser = []; foreach ($assignments as $assignment) { $byUser[$assignment['user_id']][] = $assignment['role_name']; } ?>
<?php foreach ($users as $user): ?><tr data-user-row><td><strong><?= esc($user['username']) ?></strong><?php if ((int) $user['id'] === (int) session('user_id')): ?> <small class="text-muted">(Tú)</small><?php endif ?></td><td><?= esc($user['email']) ?></td><td><?php foreach ($byUser[$user['id']] ?? [] as $name): ?><span class="access-tag"><?= esc($name) ?></span><?php endforeach ?><?php if (empty($byUser[$user['id']])): ?><span class="text-muted">Sin rol asignado</span><?php endif ?></td><td class="text-right"><div class="access-row-actions"><a class="btn btn-sm btn-outline-primary" href="<?= site_url('users/edit/' . $user['id']) ?>">Editar accesos<span class="sr-only"> de <?= esc($user['username']) ?></span></a><?php if ((int) $user['id'] !== (int) session('user_id')): ?><form method="post" action="<?= site_url('users/delete/' . $user['id']) ?>" data-confirm="¿Eliminar este usuario y sus asignaciones de acceso? Esta acción no se puede deshacer."><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Eliminar usuario <?= esc($user['username']) ?>"><i class="fas fa-trash-alt" aria-hidden="true"></i></button></form><?php endif ?></div></td></tr><?php endforeach ?>
</tbody></table></div>
</section>
<?= $this->endSection() ?>
<?= $this->section('js-page') ?><script src="<?= base_url('js/access.js') ?>"></script><?= $this->endSection() ?>
