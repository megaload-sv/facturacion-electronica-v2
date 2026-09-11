
<h2>Editar Permisos para: <?= esc($menu['name']) ?></h2>

<form method="post" action="/permission_menu/store">
<?= csrf_field() ?>
    <input type="hidden" name="menu_item_id" value="<?= esc($menu['id']) ?>">

    <label>Seleccionar Permisos</label><br>

    <?php foreach ($permissions as $permission): ?>
        <input type="checkbox" name="permissions[]" value="<?= esc($permission['id']) ?>"
            <?php if (in_array($permission['id'], array_column($assigned_permissions, 'permission_id'))): ?>
                checked
            <?php endif; ?>
        >
        <?= esc($permission['permission_name']) ?><br>
    <?php endforeach; ?>

    <button type="submit">Guardar</button>
</form>
