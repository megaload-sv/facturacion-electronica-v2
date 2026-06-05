<?= $this->extend('layouts/main')?>

<?= $this->section('title')?>
Asignar permiso a Rol
<?= $this->endSection()?>

<?= $this->section('content')?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="<?= base_url('permission_roles/store') ?>" method="post">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Asignar Permiso</h3>
                    </div>
                    <div class="card-body">
                        <!-- Selección del Rol -->
                        <div class="form-group">
                            <label for="role_id">Rol</label>
                            <select name="role_id" id="role_id" class="form-control">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>"><?= $role['role_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Selección del Permiso -->
                        <div class="form-group">
                            <label for="permission_id">Permiso</label>
                            <select name="permission_id" id="permission_id" class="form-control">
                                <?php foreach ($permissions as $permission): ?>
                                    <option value="<?= $permission['id'] ?>"><?= $permission['permission_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Botón de asignar -->
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Asignar
                        </button>
                        <a href="<?= base_url('roles') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a la lista de roles
                        </a>
                    </div>
                </div>
            </form>
         </div>
    </div>
</div>

<?= $this->endSection()?>