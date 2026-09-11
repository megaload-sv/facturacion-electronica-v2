<?= $this->extend('layouts/main')?>

<?= $this->section('title')?>
Editar Permisos
<?= $this->endSection()?>

<?= $this->section('content')?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="<?= base_url('permissions/update/' . $permission['id']) ?>" method="post">
<?= csrf_field() ?>
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Actualizar Permiso</h3>
                    </div>
                    <div class="card-body">
                        <!-- Nombre del Permiso -->
                        <div class="form-group">
                            <label for="permission_name">Nombre del Permiso</label>
                            <input type="text" name="permission_name" id="permission_name" class="form-control" value="<?= esc($permission['permission_name']) ?>" required>
                        </div>

                        <!-- Descripción -->
                        <div class="form-group">
                            <label for="description">Descripción</label>
                            <textarea name="description" id="description" class="form-control"><?= esc($permission['description']) ?></textarea>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Actualizar Permiso
                        </button>
                        <a href="<?= base_url('permissions') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a la lista de permisos
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>