<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Permisos de menú | Megaload<?= $this->endSection() ?>
<?= $this->section('content') ?>
<h2>Gestión de Permisos de Menú</h2>

<div class="table-responsive">
<table class="table table-bordered table-striped" data-datatable>
    <thead>
    <tr>
        <th>Ítem de Menú</th>
        <th data-table-actions>Acciones</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($menu_items as $menu_item): ?>
        <tr>
            <td><?= esc($menu_item['name']) ?></td>
            <td>
                <a class="btn btn-sm btn-outline-primary" href="<?= base_url('permission_menu/edit/' . $menu_item['id']) ?>">Gestionar Permisos</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?= $this->endSection() ?>
