<h2>Eliminar Permiso</h2>

<p>¿Estás seguro de que deseas eliminar el permiso <strong><?= $permission['permission_name'] ?></strong>?</p>

<form action="/permissions/delete/<?= $permission['id'] ?>" method="post">
    <button type="submit">Eliminar</button>
    <a href="/permissions">Cancelar</a>
</form>
