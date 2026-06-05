<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
    Sistema de Facturacion Electronica | Gestio Usuarios
<?= $this->endSection() ?>

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

<?= $this->section('content-header') ?>
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0">Dashboard</h1>
        </div><!-- /.col -->
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                <li class="breadcrumb-item active">Gestion de Menu</li>
            </ol>
        </div><!-- /.col -->
    </div><!-- /.row -->
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h2>Lista de Usuarios</h2>
<!-- Botón para agregar un nuevo usuario -->
<a href="<?= base_url('users/create') ?>" class="btn btn-primary mb-3">
    <i class="fas fa-user-plus"></i> Agregar Nuevo Usuario
</a>

<!-- Tabla de usuarios -->
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= $user['username'] ?></td>
                <td><?= $user['email'] ?></td>
                <td>
                    <!-- Botón para editar -->
                    <a href="<?= base_url('users/edit/' . $user['id']) ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Editar
                    </a>

                    <!-- Botón para eliminar (con confirmación) -->
                    <a href="<?= base_url('users/delete/' . $user['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                        <i class="fas fa-trash-alt"></i> Eliminar
                    </a>

                    <!-- Enlace para ver permisos -->
                    <a href="<?= base_url('users/permissions/' . $user['id']) ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-key"></i> Ver Permisos
                    </a>

                    <!-- Enlace para ver roles -->
                    <a href="<?= base_url('users/roles/' . $user['id']) ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-users-cog"></i> Ver Roles
                    </a>

                    <!-- Enlace para gestionar roles -->
                    <a href="<?= base_url('users/roles/manage/' . $user['id']) ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-tasks"></i> Gestionar Roles
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
