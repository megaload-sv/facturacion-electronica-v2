<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Actualizar Usuario
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="<?= base_url('users/update/' . $user['id']) ?>" method="post">
<?= csrf_field() ?>
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Actualizar Usuario</h3>
                    </div>
                    <div class="card-body">
                        <!-- Nombre -->
                        <div class="form-group">
                            <label for="user_name">Nombre</label>
                            <input type="text" name="user_name" id="user_name" class="form-control" value="<?= esc($user['username']) ?>" required>
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= esc($user['email']) ?>" required>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Actualizar
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
