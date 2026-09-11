<?= $this->renderSection('menu'); ?>
<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
        data-accordion="false">
        <!-- Add icons to the links using the .nav-icon class
             with font-awesome or any other icon font library -->
        <?php foreach ($menu as $menuItem): ?>
            <li class="nav-item">
                <a href="<?= esc($menuItem['url']) ?>" class="nav-link">
                    <i class="nav-icon <?= esc($menuItem['icon']) ?>"></i>
                    <p>
                        <?= esc($menuItem['name']) ?>
                        <?php if (!empty($menuItem['children'])): ?>
                            <i class="right fas fa-angle-left"></i>
                        <?php endif; ?>
                    </p>
                </a>
                <?php if (!empty($menuItem['children'])): ?>
                    <ul class="nav nav-treeview">
                        <?php foreach ($menuItem['children'] as $childItem): ?>
                            <li class="nav-item">
                                <a href="<?= esc($childItem['url']) ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?= esc($childItem['name']) ?></p>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>

        <!--<li class="nav-item">
            <a href="<?= base_url() ?>dashboard" class="nav-link">
                <i class="nav-icon fas fa-th"></i>
                <p>
                    Dashboard
                </p>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= base_url() ?>facturas" class="nav-link">
                <i class="nav-icon fas fa-table"></i>
                <p>
                    Facturas
                </p>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= base_url() ?>permissions" class="nav-link">
                <i class="nav-icon fas fa-table"></i>
                <p>
                    Permisos
                </p>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= base_url() ?>roles" class="nav-link">
                <i class="nav-icon fas fa-table"></i>
                <p>
                    Roles
                </p>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= base_url() ?>permission_roles" class="nav-link">
                <i class="nav-icon fas fa-table"></i>
                <p>
                    Permisos por Rol
                </p>
            </a>
        </li>
        <li class="nav-item">
            <form method="post" action="<?= base_url() ?>logout" style="display:inline;"><?= csrf_field() ?><button type="submit" class="nav-link">
                <i class="nav-icon fas fa-table"></i>
                <p>
                    Salir
                </p>
            </button></form>
        </li>
    </ul>-->
</nav>