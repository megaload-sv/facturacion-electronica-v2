<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Sistema de Facturacion Electronica | Gestion Roles
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

<?= $this->section('content'); ?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="/roles/store" method="post">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Agregar Rol</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="role_name" class="form-label">Nombre del Rol</label>
                            <input type="text" class="form-control" id="role_name" name="role_name" required>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="description" name="description"></textarea>
                        </div>
                    </div>
                    
                    <!-- Botón de guardar -->
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                        <a href="/roles" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a la lista de roles
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
