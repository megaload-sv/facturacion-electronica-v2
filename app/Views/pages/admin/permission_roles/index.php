<?= $this->extend('layouts/main')?>

<?= $this->section('title')?>
Lista de Permisos por rol
<?= $this->endSection()?>

<?= $this->section('content')?>

<a href="<?= base_url('permission_roles/create') ?>" class="btn btn-primary mb-3">
    <i class="fas fa-plus"></i> Asignar Nuevo Permiso a un Rol
</a>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID del Rol</th>
                <th>Nombre del Rol</th>
                <th>ID del Permiso</th>
                <th>Nombre del Permiso</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($permissionRoles)): ?>
            <?php foreach ($permissionRoles as $permissionRole): ?>
                <tr>
                    <td><?= $permissionRole['role_id'] ?></td>
                    <td><?= $permissionRole['role_name'] ?></td>
                    <td><?= $permissionRole['permission_id'] ?></td>
                    <td><?= $permissionRole['permission_name'] ?></td>
                    <td>
                        <a href="<?= base_url('permission_roles/delete/' . $permissionRole['role_id'] . '/' . $permissionRole['permission_id']) ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('¿Estás seguro de eliminar este permiso del rol?')">
                            <i class="fas fa-trash-alt"></i> Quitar Permiso
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" class="text-center">No hay asignaciones de permisos a roles.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<a href="<?= base_url('roles') ?>" class="btn btn-secondary mt-3">
    <i class="fas fa-arrow-left"></i> Volver a la lista de roles
</a>

<?= $this->endSection()?>