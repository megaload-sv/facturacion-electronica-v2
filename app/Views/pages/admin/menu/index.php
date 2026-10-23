<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Gestión de menús | Megaload<?= $this->endSection() ?>

<?= $this->section('content-header') ?>
<div class="ml-page-heading">
    <div><span class="ml-eyebrow">ADMINISTRACIÓN / NAVEGACIÓN</span><h1>Gestión de menús</h1><p>Organice los accesos y la estructura de navegación de su sistema.</p></div>
    <a href="<?= site_url('menu/create') ?>" class="btn btn-primary ml-primary-action"><i class="fas fa-plus mr-2" aria-hidden="true"></i> Crear nuevo ítem</a>
</div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$groups = [];
foreach ($menus as $item) {
    $groups[(int) ($item['parent_id'] ?? 0)][] = $item;
}
$parents = $groups[0] ?? [];
$childCount = count($menus) - count($parents);
?>
<link rel="stylesheet" href="<?= base_url('css/menu-admin.css') ?>?v=1">
<div class="menu-admin">
    <div class="menu-summary" aria-label="Resumen del menú">
        <div><span class="menu-summary-icon"><i class="fas fa-layer-group" aria-hidden="true"></i></span><div><strong><?= count($menus) ?></strong><span>Ítems en total</span></div></div>
        <div><span class="menu-summary-icon"><i class="fas fa-th-large" aria-hidden="true"></i></span><div><strong><?= count($parents) ?></strong><span>Menús principales</span></div></div>
        <div><span class="menu-summary-icon"><i class="fas fa-project-diagram" aria-hidden="true"></i></span><div><strong><?= $childCount ?></strong><span>Submenús</span></div></div>
    </div>
    <div class="menu-toolbar">
        <div><h2>Estructura de navegación</h2><p>Menús principales y sus accesos asociados.</p></div>
        <div class="menu-search"><i class="fas fa-search" aria-hidden="true"></i><label for="menu-search" class="sr-only">Buscar menú por nombre o ruta</label><input id="menu-search" type="search" placeholder="Buscar por nombre o ruta…" autocomplete="off"></div>
    </div>
    <div class="menu-grid">
    <?php
    $renderItem = static function (array $item, array $ancestors = []) use (&$renderItem, $groups): void {
        $id = (int) $item['id'];
        if (isset($ancestors[$id])) { return; }
        $ancestors[$id] = true;
        $children = $groups[$id] ?? [];
        $name = (string) $item['name'];
        $icon = trim((string) ($item['icon'] ?? '')) ?: 'fas fa-link';
    ?>
        <li class="menu-node">
            <div class="menu-item-row">
                <span class="menu-item-icon"><i class="<?= esc($icon, 'attr') ?>" aria-hidden="true"></i></span>
                <div class="menu-item-info"><h3><?= esc($name) ?></h3><span class="menu-route"><?= esc($item['url'] ?? '') ?: 'Sin ruta asignada' ?></span></div>
                <div class="menu-actions">
                    <a href="<?= site_url('menu/edit/' . $id) ?>" class="menu-action" aria-label="<?= esc('Editar ' . $name, 'attr') ?>" title="Editar"><i class="far fa-edit" aria-hidden="true"></i></a>
                    <form method="post" action="<?= site_url('menu/delete/' . $id) ?>" data-confirm="<?= esc('¿Desea eliminar el ítem «' . $name . '»?', 'attr') ?>"><?= csrf_field() ?><button type="submit" class="menu-action menu-action-delete" aria-label="<?= esc('Eliminar ' . $name, 'attr') ?>" title="Eliminar"><i class="far fa-trash-alt" aria-hidden="true"></i></button></form>
                </div>
            </div>
            <?php if ($children): ?><ul class="menu-children"><?php foreach ($children as $child) { $renderItem($child, $ancestors); } ?></ul><?php endif; ?>
        </li>
    <?php }; ?>
    <?php foreach ($parents as $parent): ?>
        <article class="menu-group">
            <div class="menu-group-label"><span>MENÚ PRINCIPAL</span><span><?= count($groups[(int) $parent['id']] ?? []) ?> accesos directos</span></div>
            <ul class="menu-tree"><?php $renderItem($parent); ?></ul>
        </article>
    <?php endforeach; ?>
    </div>
    <div class="menu-empty" id="menu-empty" <?= $parents ? 'hidden' : '' ?>><span class="menu-empty-icon"><i class="fas fa-sitemap" aria-hidden="true"></i></span><h2><?= $parents ? 'No encontramos coincidencias' : 'Su navegación empieza aquí' ?></h2><p><?= $parents ? 'Pruebe con otro nombre o ruta para encontrar un menú.' : 'Cree su primer ítem para organizar los accesos del sistema.' ?></p><?php if (!$parents): ?><a class="btn btn-primary" href="<?= site_url('menu/create') ?>">Crear primer ítem</a><?php endif; ?></div>
    <p class="menu-footnote"><i class="fas fa-info-circle mr-1" aria-hidden="true"></i> Los accesos disponibles para cada usuario dependen de sus roles y permisos.</p>
    <div id="menu-search-status" class="sr-only" role="status" aria-live="polite"></div>
</div>
<?= $this->endSection() ?>

<?= $this->section('js-page') ?>
<script>
(() => {
    const search = document.getElementById('menu-search');
    const groups = Array.from(document.querySelectorAll('.menu-admin .menu-group'));
    const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
    search.addEventListener('input', () => {
        const query = normalize(search.value.trim());
        let visible = 0;
        groups.forEach(group => {
            const content = Array.from(group.querySelectorAll('.menu-item-info')).map(item => item.textContent).join(' ');
            group.hidden = !normalize(content).includes(query);
            if (!group.hidden) visible++;
        });
        document.getElementById('menu-empty').hidden = visible > 0;
        document.getElementById('menu-search-status').textContent = `${visible} menús principales encontrados`;
    });
})();
</script>
<?= $this->endSection() ?>
