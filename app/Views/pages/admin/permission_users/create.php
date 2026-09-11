<?= $this->extend('layouts/main')?>

<?= $this->section('title') ?>
Crear permiso
<?= $this->endSection()?>

<?= $this->section('content')?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
        <form action="<?= base_url('permission_users/store') ?>" method="post">
<?= csrf_field() ?>
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Asignar Permiso</h3>
                </div>
                <div class="card-body">
                    <!-- Usuario -->
                    <div class="form-group">
                        <label for="user_id">Usuario</label>
                        <select name="user_id" id="user_id" class="form-control">
                            <?php foreach ($users as $user): ?>
                                <option value="<?= esc($user['id']) ?>"><?= esc($user['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Permiso -->
                    <div class="form-group">
                        <label for="permission_id">Permiso</label>
                        <select name="permission_id" id="permission_id" class="form-control">
                            <?php foreach ($permissions as $permission): ?>
                                <option value="<?= esc($permission['id']) ?>"><?= esc($permission['permission_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Botón de asignar -->
                <div class="card-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Asignar Permiso
                    </button>
                    <a href="<?= base_url('permission_users') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a la lista de asignaciones
                    </a>
                </div>
            </div>
        </form>
        </div>
    </div>
</div>
<?= $this->endSection()?>
