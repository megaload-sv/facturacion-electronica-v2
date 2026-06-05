<?= $this->extend('layouts/main')?>

<?= $this->section('title')?>
Gestión de Menú por Roles
<?= $this->endSection()?>

<?= $this->section('user-info'); ?>
    <!-- Sidebar user panel (optional) -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
            <img src="<?= base_url('dist/img/user2-160x160.jpg')?>" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
            <a href="#" class="d-block"><?= session()->get('username'); ?></a>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('content-header') ?>
    <div class="row mb-2">
        <div class="col-sm-6">
        </div><!-- /.col -->
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                <li class="breadcrumb-item active">Gestión de Menú por Roles</li>
            </ol>
        </div><!-- /.col -->
    </div><!-- /.row -->
<?= $this->endSection() ?>

<?= $this->section('content')?>
<div class="container mt-5">

    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th style="width: 75px;">Rol</th>
                <th style="width: 75px;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $role): ?>
                <tr>
                    <td><?= $role['role_name']; ?></td>
                    <td class="text-nowrap">
                        <a href="/role_menu/edit/<?= $role['id']; ?>" class="btn btn-sm btn-primary d-inline-block">
                            <i class="fas fa-edit"></i> Gestionar Menú
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection()?>

