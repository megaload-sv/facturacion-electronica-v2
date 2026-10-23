<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Editar permisos de menú | Megaload<?= $this->endSection() ?>
<?= $this->section('content-header') ?>
<div class="ml-page-heading">
    <div><span class="ml-eyebrow">ADMINISTRACIÓN / PERMISOS DE MENÚ</span><h1>Configurar permisos</h1><p>Seleccione los permisos asociados a <?= esc($menu_item['name']) ?>.</p></div>
    <a class="btn btn-outline-secondary" href="<?= site_url('permission_menu') ?>"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i> Volver al listado</a>
</div>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('css/permission-menu.css') ?>?v=1">
<?php $assignedIds = array_column($assigned_permissions, 'permission_id'); ?>
<div class="pm-page pm-editor">
    <aside class="ml-panel pm-context" aria-labelledby="pm-context-title">
        <span class="pm-context-icon"><i class="<?= esc(trim($menu_item['icon'] ?? '') ?: 'fas fa-link', 'attr') ?>" aria-hidden="true"></i></span>
        <span class="ml-eyebrow">ÍTEM SELECCIONADO</span><h2 id="pm-context-title"><?= esc($menu_item['name']) ?></h2>
        <span class="pm-route"><?= esc($menu_item['url'] ?? '') ?: 'Sin ruta asignada' ?></span>
        <div class="pm-context-help"><i class="fas fa-shield-alt" aria-hidden="true"></i><p>Revise la selección y guarde los cambios para actualizar los permisos de este ítem.</p></div>
    </aside>
    <section class="ml-panel pm-permissions" aria-labelledby="pm-permissions-title">
        <div class="pm-panel-heading"><div><span class="ml-eyebrow">CONFIGURACIÓN DE ACCESO</span><h2 id="pm-permissions-title">Permisos asociados</h2><p>Marque los permisos que desea vincular al ítem.</p></div><span class="pm-count"><?= count($permissions) ?></span></div>
        <form method="post" action="<?= site_url('permission_menu/store') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="menu_item_id" value="<?= (int) $menu_item['id'] ?>">
            <fieldset><legend class="sr-only">Seleccionar permisos para <?= esc($menu_item['name']) ?></legend>
                <div class="pm-options">
                <?php foreach ($permissions as $permission): ?>
                    <div class="pm-option">
                        <input id="permission-<?= (int) $permission['id'] ?>" type="checkbox" name="permissions[]" value="<?= (int) $permission['id'] ?>" <?= in_array($permission['id'], $assignedIds) ? 'checked' : '' ?>>
                        <label for="permission-<?= (int) $permission['id'] ?>"><span><?= esc($permission['permission_name']) ?></span><?php if (!empty($permission['description'])): ?><small><?= esc($permission['description']) ?></small><?php endif; ?></label>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php if (!$permissions): ?><div class="pm-empty"><i class="fas fa-key mb-3" aria-hidden="true"></i><h3>No hay permisos disponibles</h3><p>Los permisos aparecerán aquí cuando estén registrados en el sistema.</p></div><?php endif; ?>
            </fieldset>
            <div class="pm-form-footer"><span>Los cambios se aplican al guardar.</span><div><a class="btn btn-outline-secondary" href="<?= site_url('permission_menu') ?>">Cancelar</a><button type="submit" class="btn btn-primary" <?= !$permissions ? 'disabled' : '' ?>><i class="far fa-save mr-2" aria-hidden="true"></i> Guardar permisos</button></div></div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
