<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Permisos de menú | Megaload<?= $this->endSection() ?>
<?= $this->section('content-header') ?>
<div class="ml-page-heading">
    <div><span class="ml-eyebrow">ADMINISTRACIÓN / NAVEGACIÓN</span><h1>Permisos de menú</h1><p>Organice los permisos asociados a cada acceso del sistema.</p></div>
    <a class="btn btn-outline-secondary" href="<?= site_url('menu') ?>"><i class="fas fa-sitemap mr-2" aria-hidden="true"></i> Ver estructura del menú</a>
</div>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('css/permission-menu.css') ?>?v=1">
<?php
$menuNames = array_column($menu_items, 'name', 'id');
$mainCount = count(array_filter($menu_items, static fn ($item) => empty($item['parent_id'])));
?>
<div class="pm-page">
    <div class="pm-overview" aria-label="Resumen de navegación">
        <div class="pm-stat"><span class="pm-icon"><i class="fas fa-sitemap" aria-hidden="true"></i></span><div><strong><?= count($menu_items) ?></strong><span>Ítems de navegación</span></div></div>
        <div class="pm-stat"><span class="pm-icon"><i class="fas fa-layer-group" aria-hidden="true"></i></span><div><strong><?= $mainCount ?></strong><span>Menús principales</span></div></div>
        <div class="pm-stat"><span class="pm-icon"><i class="fas fa-key" aria-hidden="true"></i></span><div><strong><?= count($permissions) ?></strong><span>Permisos disponibles</span></div></div>
    </div>
    <section class="ml-panel pm-directory" aria-labelledby="pm-directory-title">
        <div class="pm-panel-heading"><div><span class="ml-eyebrow">DIRECTORIO DE ACCESOS</span><h2 id="pm-directory-title">Navegación del sistema <span class="pm-count"><?= count($menu_items) ?></span></h2><p>Encuentre un ítem y gestione sus permisos asociados.</p></div><span class="pm-section-icon"><i class="fas fa-shield-alt" aria-hidden="true"></i></span></div>
        <div class="table-responsive">
            <table class="table table-hover pm-table" data-datatable>
                <caption class="sr-only">Ítems de navegación, rutas, ubicación y gestión de permisos</caption>
                <thead><tr><th scope="col">Ítem de menú</th><th scope="col">Ruta</th><th scope="col">Ubicación</th><th scope="col" class="text-right" data-table-actions>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($menu_items as $item): ?>
                    <tr>
                        <td><div class="pm-item"><span class="pm-item-icon"><i class="<?= esc(trim($item['icon'] ?? '') ?: 'fas fa-link', 'attr') ?>" aria-hidden="true"></i></span><strong><?= esc($item['name']) ?></strong></div></td>
                        <td><span class="pm-route"><?= esc($item['url'] ?? '') ?: 'Sin ruta asignada' ?></span></td>
                        <td><?php if (empty($item['parent_id'])): ?><span class="pm-tag">Menú principal</span><?php else: ?><span class="pm-parent"><i class="fas fa-level-up-alt fa-rotate-90 mr-2" aria-hidden="true"></i><?= esc($menuNames[$item['parent_id']] ?? 'Menú padre no disponible') ?></span><?php endif; ?></td>
                        <td class="text-right"><a class="btn btn-sm btn-outline-primary pm-manage" href="<?= site_url('permission_menu/edit/' . (int) $item['id']) ?>"><i class="fas fa-sliders-h mr-2" aria-hidden="true"></i> Gestionar permisos<span class="sr-only"> de <?= esc($item['name']) ?></span></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <p class="pm-note"><i class="fas fa-info-circle" aria-hidden="true"></i> Los roles y permisos del usuario determinan su acceso al sistema.</p>
</div>
<?= $this->endSection() ?>
