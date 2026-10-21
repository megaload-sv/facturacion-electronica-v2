<?= $this->extend('layouts/main')?>

<?= $this->section('title')?>
Permisos a roles
<?= $this->endSection()?>

<?= $this->section('content')?>

<h2 class="mb-4">Permisos Asignados al Rol: <?= esc($role['role_name']) ?></h2>

<a href="<?= base_url('permission_roles/create?role_id=' . $role['id']) ?>" class="btn btn-primary mb-3">
    <i class="fas fa-plus"></i> Asignar Nuevos Permisos
</a>

<div class="table-responsive">
    <table class="table table-bordered table-striped" data-datatable>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre del Permiso</th>
                <th>Descripción</th>
                <th data-table-actions>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($assignedPermissions)): ?>
            <?php foreach ($assignedPermissions as $permission): ?>
                <tr>
                    <td><?= esc($permission['id']) ?></td>
                    <td><?= esc($permission['permission_name']) ?></td>
                    <td><?= esc($permission['description']) ?></td>
                    <td>
                        <form method="post" action="<?= base_url('permission_roles/delete/' . $role['id'] . '/' . $permission['id']) ?>" style="display:inline;" data-confirm="¿Estás seguro de eliminar este permiso del rol?"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-outline-danger">
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
