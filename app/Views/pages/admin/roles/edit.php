<?= $this->extend('layouts/main')?>

<?= $this->section('title')?>
Editar Roles
<?= $this->endSection()?>

<?= $this->section('content')?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="<?= base_url('roles/update/' . $role['id']) ?>" method="post">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Actualizar Rol</h3>
                    </div>
                    <div class="card-body">
                        <!-- Nombre del Rol -->
                        <div class="form-group">
                            <label for="role_name">Nombre del Rol</label>
                            <input type="text" name="role_name" id="role_name" class="form-control" value="<?= $role['role_name'] ?>" required>
                        </div>

                        <!-- Descripción del Rol -->
                        <div class="form-group">
                            <label for="description">Descripción</label>
                            <textarea name="description" id="description" class="form-control" rows="4" required><?= $role['description'] ?></textarea>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Actualizar
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