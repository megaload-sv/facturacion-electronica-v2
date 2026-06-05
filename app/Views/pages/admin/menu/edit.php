<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Sistema de Facturacion Electronica | Gestion Menu
<?= $this->endSection()?>

<?= $this->section('user-info'); ?>
    <!-- Sidebar user panel (optional) -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
            <img src="<?= base_url()?>dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
            <a href="#" class="d-block"><?= session()->get('username'); ?></a>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form method="post" action="/menu/update/<?= esc($menuEdit['id']); ?>">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Editar Ítem de Menú</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= esc($menuEdit['name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="url" class="form-label">URL</label>
                            <input type="text" class="form-control" id="url" name="url" value="<?= esc($menuEdit['url']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="parent_id" class="form-label">Ítem Padre</label>
                            <select class="form-control" id="parent_id" name="parent_id">
                                <option value="">Sin padre</option>
                                <?php foreach ($menus as $m): ?>
                                    <option value="<?= esc($m['id']); ?>" <?= $m['id'] == $menuEdit['parent_id'] ? 'selected' : ''; ?>>
                                        <?= esc($m['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="order" class="form-label">Orden</label>
                            <input type="number" class="form-control" id="order" name="order" value="<?= esc($menuEdit['order']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="icon" class="form-label">Ícono</label>
                            <input type="text" class="form-control" id="icon" name="icon" value="<?= esc($menuEdit['icon']); ?>">
                        </div>
                        <!-- Select múltiple para roles -->
                        <div class="form-group">
                                <label for="roles" class="form-label">Asignar Roles</label>
                                <select class="custom-select2 form-multi-select form-control" id="roles" name="roles[]" multiple>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['id']; ?>" <?= $role['id'] == $menuEdit['role_id'] ? 'selected' : ''; ?>><?= $role['role_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                        </div>
                    </div>
                    
                    <!-- Botón de guardar -->
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="<?= base_url('menu') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a la lista
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<?= $this->endSection() ?>
