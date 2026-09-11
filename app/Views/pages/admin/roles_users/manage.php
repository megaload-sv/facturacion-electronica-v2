<?= $this->extend('layouts/main')?>

<?= $this->section('title')?>
Gestion de Roles
<?= $this->endSection()?>

<?= $this->section('content')?>

<h2 class="mb-4">Gestionar Roles del Usuario: <?= esc($user['username']) ?></h2>

<form action="<?= base_url('users/roles/assign') ?>" method="post">
<?= csrf_field() ?>
    <input type="hidden" name="user_id" value="<?= esc($user['id']) ?>">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="role_id">Asignar Nuevo Rol</label>
                    <select name="role_id" id="role_id" class="form-control">
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= esc($role['id']) ?>"><?= esc($role['role_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Asignar Rol
                </button>
            </div>
        </div>
    </div>
</form>

<h3 class="mt-5">Roles Actuales</h3>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID del Rol</th>
                <th>Nombre del Rol</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($assignedRoles)): ?>
            <?php foreach ($assignedRoles as $role): ?>
                <tr>
                    <td><?= esc($role['id']) ?></td>
                    <td><?= esc($role['role_name']) ?></td>
                    <td><?= esc($role['description']) ?></td>
                    <td>
                        <form method="post" action="<?= base_url('users/roles/remove/' . $user['id'] . '/' . $role['id']) ?>" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este rol del usuario?')"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-danger">
                           <i class="fas fa-trash-alt"></i> Quitar Rol
                        </button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" class="text-center">No hay roles asignados a este usuario.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<a href="<?= base_url('users') ?>" class="btn btn-secondary mt-3">
    <i class="fas fa-arrow-left"></i> Volver a la lista de usuarios
</a>

<?= $this->endSection()?>