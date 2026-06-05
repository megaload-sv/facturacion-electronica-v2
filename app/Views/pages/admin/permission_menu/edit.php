
<h2>Editar Permisos para: <?= $menu['name']; ?></h2>

<form method="post" action="/permission_menu/store">
    <input type="hidden" name="menu_item_id" value="<?= $menu['id']; ?>">

    <label>Seleccionar Permisos</label><br>

    <?php foreach ($permissions as $permission): ?>
        <input type="checkbox" name="permissions[]" value="<?= $permission['id']; ?>"
            <?php if (in_array($permission['id'], array_column($assigned_permissions, 'permission_id'))): ?>
                checked
            <?php endif; ?>
        >
        <?= $permission['permission_name']; ?><br>
    <?php endforeach; ?>

    <button type="submit">Guardar</button>
</form>
