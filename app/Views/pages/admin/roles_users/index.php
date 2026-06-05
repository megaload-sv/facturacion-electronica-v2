<?= $this->extend('layouts/main')?>


<?= $this->section('content')?>
<h2 class="mb-4">Roles Asignados al Usuario: <?= $user['username'] ?></h2>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID del Rol</th>
                <th>Nombre del Rol</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($assignedRoles)): ?>
            <?php foreach ($assignedRoles as $role): ?>
                <tr>
                    <td><?= $role['id'] ?></td>
                    <td><?= $role['role_name'] ?></td>
                    <td><?= $role['description'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="3" class="text-center">No hay roles asignados a este usuario.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<a href="<?= base_url('users') ?>" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Volver a la lista de usuarios
</a>
<?= $this->endSection()?>
