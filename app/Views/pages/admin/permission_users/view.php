<?= $this->extend('layouts/main')?>

<?= $this->section('title') ?>
Permisos a Usuarios
<?= $this->endSection()?>

<?= $this->section('content')?>

<h2 class="mb-4">Permisos Asignados al Usuario: <?= $user['username'] ?></h2>

<a href="<?= base_url('permission_users/create?user_id=' . $user['id']) ?>" class="btn btn-primary mb-3">
    <i class="fas fa-plus"></i> Asignar Nuevos Permisos
</a>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID del Permiso</th>
                <th>Nombre del Permiso</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($assignedPermissions)): ?>
            <?php foreach ($assignedPermissions as $permission): ?>
                <tr>
                    <td><?= $permission['id'] ?></td>
                    <td><?= $permission['permission_name'] ?></td>
                    <td><?= $permission['description'] ?></td>
                    <td>
                        <a href="<?= base_url('permission_users/delete/' . $user['id'] . '/' . $permission['id']) ?>" 
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('¿Estás seguro de quitar este permiso?')">
                           <i class="fas fa-trash-alt"></i> Quitar Permiso
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" class="text-center">No hay permisos asignados a este usuario.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<a href="<?= base_url('users') ?>" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Volver a la lista de usuarios
</a>
<?= $this->endSection()?>