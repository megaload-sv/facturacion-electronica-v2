<?= $this->extend('layouts/main')?>

<?= $this->section('title')?>
Asignar permiso a usuario
<?= $this->endSection()?>

<?= $this->section('content') ?>

<h2 class="mb-4">Lista de Permisos Asignados a Usuarios</h2>

<a href="<?= base_url('permission_users/create') ?>" class="btn btn-primary mb-3">
    <i class="fas fa-plus"></i> Asignar Nuevo Permiso a un Usuario
</a>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID del Usuario</th>
                <th>Nombre del Usuario</th>
                <th>ID del Permiso</th>
                <th>Nombre del Permiso</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($permissionUsers)): ?>
            <?php foreach ($permissionUsers as $permissionUser): ?>
                <tr>
                    <td><?= esc($permissionUser['user_id']) ?></td>
                    <td><?= esc($permissionUser['username']) ?></td>
                    <td><?= esc($permissionUser['permission_id']) ?></td>
                    <td><?= esc($permissionUser['permission_name']) ?></td>
                    <td>
                        <a href="<?= base_url('permission_users/edit/' . $permissionUser['user_id'] . '/' . $permissionUser['permission_id']) ?>" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <form method="post" action="<?= base_url('permission_users/delete/' . $permissionUser['user_id'] . '/' . $permissionUser['permission_id']) ?>" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este permiso del usuario?')"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash-alt"></i> Quitar Permiso
                        </button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" class="text-center">No hay asignaciones de permisos a usuarios.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<a href="<?= base_url('users') ?>" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Volver a la lista de usuarios
</a>


<?= $this->endSection()?>