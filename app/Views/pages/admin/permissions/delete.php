<h2>Eliminar Permiso</h2>

<p>¿Estás seguro de que deseas eliminar el permiso <strong><?= esc($permission['permission_name']) ?></strong>?</p>

<form action="/permissions/delete/<?= esc($permission['id']) ?>" method="post">
<?= csrf_field() ?>
    <button type="submit">Eliminar</button>
    <a href="/permissions">Cancelar</a>
</form>
