<?= $this->renderSection('menu'); ?>
<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
        data-accordion="false">
        <!-- Add icons to the links using the .nav-icon class
             with font-awesome or any other icon font library -->
        <?php foreach ($menu as $menuItem): ?>
            <li class="nav-item">
                <a href="<?= $menuItem['url'] ?>" class="nav-link">
                    <i class="nav-icon <?= $menuItem['icon'] ?>"></i>
                    <p>
                        <?= $menuItem['name'] ?>
                        <?php if (!empty($menuItem['children'])): ?>
                            <i class="right fas fa-angle-left"></i>
                        <?php endif; ?>
                    </p>
                </a>
                <?php if (!empty($menuItem['children'])): ?>
                    <ul class="nav nav-treeview">
                        <?php foreach ($menuItem['children'] as $childItem): ?>
                            <li class="nav-item">
                                <a href="<?= $childItem['url'] ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?= $childItem['name'] ?></p>
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
            <a href="<?= base_url() ?>logout" class="nav-link">
                <i class="nav-icon fas fa-table"></i>
                <p>
                    Salir
                </p>
            </a>
        </li>
    </ul>-->
</nav>