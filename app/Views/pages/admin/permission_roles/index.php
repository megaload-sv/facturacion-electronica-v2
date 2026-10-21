<?= $this->extend('layouts/main')?>

<?= $this->section('title')?>
Lista de Permisos por rol
<?= $this->endSection()?>

<?= $this->section('content')?>

<a href="<?= base_url('permission_roles/create') ?>" class="btn btn-primary mb-3">
    <i class="fas fa-plus"></i> Asignar Nuevo Permiso a un Rol
</a>

<div class="table-responsive">
    <table class="table table-bordered table-striped" data-datatable>
        <thead>
            <tr>
                <th>ID del Rol</th>
                <th>Nombre del Rol</th>
                <th>ID del Permiso</th>
                <th>Nombre del Permiso</th>
                <th data-table-actions>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($permissionRoles)): ?>
            <?php foreach ($permissionRoles as $permissionRole): ?>
                <tr>
                    <td><?= esc($permissionRole['role_id']) ?></td>
                    <td><?= esc($permissionRole['role_name']) ?></td>
                    <td><?= esc($permissionRole['permission_id']) ?></td>
                    <td><?= esc($permissionRole['permission_name']) ?></td>
                    <td>
                        <form method="post" action="<?= base_url('permission_roles/delete/' . $permissionRole['role_id'] . '/' . $permissionRole['permission_id']) ?>" style="display:inline;" data-confirm="¿Estás seguro de eliminar este permiso del rol?"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash-alt"></i> Quitar Permiso
                        </button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
        
        <?php endif; ?>
        </tbody>
    </table>
</div>

<a href="<?= base_url('roles') ?>" class="btn btn-secondary mt-3">
    <i class="fas fa-arrow-left"></i> Volver a la lista de roles
</a>

<?= $this->endSection()?>