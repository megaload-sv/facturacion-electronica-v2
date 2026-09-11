<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Sistema de Facturacion Electronica | Gestion Permisos
<?= $this->endSection()?>

<?= $this->section('user-info'); ?>
    <!-- Sidebar user panel (optional) -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
            <img src="<?= base_url() ?>dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
            <a href="#" class="d-block"><?= session()->get('username'); ?></a>
        </div>
    </div>
<?= $this->endSection() ?>


<?= $this->section('content') ?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="/permissions/store" method="post">
<?= csrf_field() ?>
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Agregar Permiso</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="permission_name" class="form-label">Nombre del Permiso</label>
                            <input type="text" class="form-control" name="permission_name" id="permission_name" required>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" id="description"></textarea>
                        </div>
                    </div>
                    
                    <!-- Botón de guardar -->
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Guardar Permiso
                        </button>
                        <a href="/permissions" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a la lista de permisos
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>