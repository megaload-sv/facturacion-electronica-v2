<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Permisos
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<a href="<?= base_url('permissions/create') ?>" class="btn btn-primary mb-3">
    <i class="fas fa-plus"></i> Agregar Permiso
</a>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($permissions as $permission): ?>
            <tr>
                <td><?= esc($permission['id']) ?></td>
                <td><?= esc($permission['permission_name']) ?></td>
                <td><?= esc($permission['description']) ?></td>
                <td>
                    <!-- Botón para editar -->
                    <a href="<?= base_url('permissions/edit/' . $permission['id']) ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Editar
                    </a>

                    <!-- Botón para eliminar con confirmación -->
                    <form method="post" action="<?= base_url('permissions/delete/' . $permission['id']) ?>" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este permiso?')"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-danger">
                        <i class="fas fa-trash-alt"></i> Eliminar
                    </button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>


<?= $this->endSection()?>
