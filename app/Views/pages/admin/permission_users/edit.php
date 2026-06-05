<?= $this->extend('layouts/main')?>

<?= $this->section('title') ?>
Editar Permisos a Usuarios
<?= $this->endSection()?>

<?= $this->section('content')?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="/permission_users/update/<?= $user_id ?>/<?= $permission_id ?>" method="post">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Actualizar Asignación</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="user_id">Usuario</label>
                            <select class="form-control" name="user_id" id="user_id">
                               <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= ($user['id'] == $user_id) ? 'selected' : '' ?>>
                                        <?= $user['username'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="permission_id">Permiso</label>
                            <select class="form-control" name="permission_id" id="permission_id">
                                <?php foreach ($permissions as $permission): ?>
                                    <option value="<?= $permission['id'] ?>" <?= ($permission['id'] == $permission_id) ? 'selected' : '' ?>>
                                        <?= $permission['permission_name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Actualizar Asignación</button>
                        <a href="/permission_users" class="btn btn-secondary">Volver a la lista de asignaciones</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection()?>
