<?= $this->extend('layouts/main')?>

<?= $this->section('title')?>
Lista de Roles
<?= $this->endSection()?>

<?= $this->section('content')?>

<h2 class="mb-4">Lista de Roles</h2>

<a href="<?= base_url('roles/create') ?>" class="btn btn-primary mb-3">
    <i class="fas fa-plus"></i> Agregar Rol
</a>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Nombre del Rol</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($roles as $role): ?>
            <tr>
                <td><?= $role['id'] ?></td>
                <td><?= $role['role_name'] ?></td>
                <td><?= $role['description'] ?></td>
                <td>
                    <a href="<?= base_url('roles/edit/' . $role['id']) ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <a href="<?= base_url('roles/delete/' . $role['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">
                        <i class="fas fa-trash-alt"></i> Eliminar
                    </a>
                    <!-- Opción para asignar permisos al rol -->
                    <a href="<?= base_url('permission_roles/create?role_id=' . $role['id']) ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-user-tag"></i> Asignar Permisos
                    </a>
                    <a href="<?= base_url('permission_roles/view/' . $role['id']) ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-eye"></i> Ver Permisos Asignados
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<a href="<?= base_url('dashboard') ?>" class="btn btn-secondary mt-3">
    <i class="fas fa-arrow-left"></i> Volver al Dashboard
</a>
<?= $this->endSection()?>