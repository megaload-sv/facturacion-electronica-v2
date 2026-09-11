<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>
Agregar Nuevo Usuario
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="<?= base_url('users/store') ?>" method="post">
<?= csrf_field() ?>
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Agregar Usuario</h3>
                    </div>
                    <div class="card-body">
                        <!-- Nombre -->
                        <div class="form-group">
                            <label for="user_name">Nombre</label>
                            <input type="text" name="user_name" id="user_name" class="form-control" placeholder="Ingrese el nombre" required>
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="Ingrese el email" required>
                        </div>

                        <!-- Contraseña -->
                        <div class="form-group">
                            <label for="password">Contraseña</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Ingrese la contraseña" required>
                        </div>
                    </div>

                    <!-- Botón de guardar -->
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                        <a href="<?= base_url('users') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a la lista de usuarios
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
