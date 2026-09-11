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
                <li class="breadcrumb-item active">Edición de Menú por Roles</li>
            </ol>
        </div><!-- /.col -->
    </div><!-- /.row -->
<?= $this->endSection() ?>

<?= $this->section('content')?>
<div class="container mt-5">
    <h2 class="mb-4">Editar Menú para el Rol: <?= esc($role['role_name']) ?></h2>

    <form method="post" action="/role_menu/store">
<?= csrf_field() ?>
        <input type="hidden" name="role_id" value="<?= esc($role['id']) ?>">

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Seleccionar Ítems de Menú</h3>
            </div>
            <div class="card-body">
                <?php foreach ($menu_items as $menu_item): ?>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="menu_items[]" value="<?= esc($menu_item['id']) ?>"
                            <?php if (in_array($menu_item['id'], array_column($assigned_menu_items, 'menu_item_id'))): ?>
                                checked
                            <?php endif; ?>
                        >
                        <label class="form-check-label"><?= esc($menu_item['name']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Guardar
                </button>
                <a href="<?= base_url('role_menu') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Regresar
                </a>
            </div>
        </div>
    </form>
</div>

<?= $this->endSection()?>