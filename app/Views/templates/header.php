<?php
$username = (string) (session()->get('username') ?: 'Usuario');
$currentPath = trim(service('request')->getUri()->getPath(), '/');
$isCurrent = static function ($url) use ($currentPath): bool {
    $path = trim((string) parse_url((string) $url, PHP_URL_PATH), '/');
    return $path !== '' && $url !== '#' && ($currentPath === $path || str_ends_with($currentPath, '/' . $path));
};
$icons = ['dashboard' => 'fas fa-th-large', 'facturas' => 'far fa-file-alt', 'seguridad' => 'fas fa-shield-alt', 'reportes' => 'fas fa-chart-bar'];
?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light" aria-label="Barra superior">
    <button class="nav-link ml-menu-toggle" data-widget="pushmenu" type="button" aria-label="Mostrar u ocultar menú"><i class="fas fa-bars" aria-hidden="true"></i></button>
    <span class="ml-top-title">Facturación electrónica <span class="ml-top-divider">/</span> <span class="text-muted">Megaload</span></span>
    <div class="ml-auto d-flex align-items-center"><span class="ml-user-name"><?= esc($username) ?></span><span class="ml-avatar" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr($username, 0, 1))) ?></span><button class="nav-link ml-menu-toggle" data-widget="fullscreen" type="button" aria-label="Alternar pantalla completa"><i class="fas fa-expand" aria-hidden="true"></i></button></div>
</nav>
<aside class="main-sidebar sidebar-dark-primary">
    <a href="<?= base_url('dashboard') ?>" class="brand-link" aria-label="Megaload, inicio"><img src="<?= base_url('images/logo_megaload_only_img.png') ?>" alt="" class="brand-image ml-brand-icon"><span class="brand-text">MEGALOAD<small>FACTURACIÓN ELECTRÓNICA</small></span></a>
    <div class="sidebar">
        <div class="ml-nav-label">ESPACIO DE TRABAJO</div>
        <nav aria-label="Navegación principal"><ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" data-accordion="false">
        <?php foreach ($menu as $menuItem): ?>
            <?php
            $active = $isCurrent($menuItem['url']);
            $childActive = false;
            foreach ($menuItem['children'] ?? [] as $child) { $childActive = $childActive || $isCurrent($child['url']); }
            $icon = $icons[mb_strtolower($menuItem['name'])] ?? $menuItem['icon'];
            ?>
            <li class="nav-item <?= $childActive ? 'menu-open' : '' ?>">
                <a href="<?= esc($menuItem['url']) ?>" class="nav-link <?= ($active || $childActive) ? 'active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?>><i class="nav-icon <?= esc($icon) ?>" aria-hidden="true"></i><p><?= esc($menuItem['name']) ?><?php if (!empty($menuItem['children'])): ?><i class="right fas fa-angle-left" aria-hidden="true"></i><?php endif; ?></p></a>
                <?php if (!empty($menuItem['children'])): ?><ul class="nav nav-treeview">
                    <?php foreach ($menuItem['children'] as $childItem): ?><li class="nav-item"><a href="<?= esc($childItem['url']) ?>" class="nav-link <?= $isCurrent($childItem['url']) ? 'active' : '' ?>" <?= $isCurrent($childItem['url']) ? 'aria-current="page"' : '' ?>><i class="nav-icon far fa-circle" aria-hidden="true"></i><p><?= esc($childItem['name']) ?></p></a></li><?php endforeach; ?>
                </ul><?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ul></nav>
        <div class="ml-sidebar-bottom"><div class="ml-workspace-note"><span class="ml-brand-line"></span><strong>Grupo Megaload</strong><small>Control y claridad en cada factura.</small></div><form method="post" action="<?= base_url('logout') ?>"><?= csrf_field() ?><button type="submit" class="ml-logout"><i class="fas fa-sign-out-alt" aria-hidden="true"></i><span>Cerrar sesión</span></button></form></div>
    </div>
</aside>
