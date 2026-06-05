<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Sistema de Facturacion Electronica | Gestion Menu
<?= $this->endSection()?>

<?= $this->section('user-info'); ?>
    <!-- Sidebar user panel (optional) -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
            <img src="dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
            <a href="#" class="d-block"><?= session()->get('username'); ?></a>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<a href="/menu/create" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Crear Nuevo Item</a>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Lista de Menús</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group">
                        <?php foreach ($menus as $menu): ?>
                            <!-- Menú Padre -->
                            <?php if ($menu['parent_id'] == 0): ?>
                                <div class="card mb-2">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                        <strong><span><?= $menu['name']; ?></span></strong>
                                        <div class="ml-auto">
                                            <a href="/menu/edit/<?= $menu['id']; ?>" class="btn btn-sm btn-warning mr-1">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <a href="/menu/delete/<?= $menu['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro?')">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Verificar si tiene submenús -->
                                    <?php
                                    $hasChildren = false;
                                    foreach ($menus as $submenu) {
                                        if ($submenu['parent_id'] == $menu['id']) {
                                            $hasChildren = true;
                                            break;
                                        }
                                    }
                                    ?>

                                    <?php if ($hasChildren): ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($menus as $submenu): ?>
                                                <?php if ($submenu['parent_id'] == $menu['id']): ?>
                                                    <!-- Menú Hijo -->
                                                    <li class="list-group-item" style="background-color: #f4eded; padding-left: 30px;">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span><?= $submenu['name']; ?></span>
                                                            <div class="ml-auto">
                                                                <a href="/menu/edit/<?= $submenu['id']; ?>" class="btn btn-sm btn-warning mr-1">
                                                                    <i class="fas fa-edit"></i> Editar
                                                                </a>
                                                                <a href="/menu/delete/<?= $submenu['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro?')">
                                                                    <i class="fas fa-trash"></i> Eliminar
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




<?= $this->endSection() ?>