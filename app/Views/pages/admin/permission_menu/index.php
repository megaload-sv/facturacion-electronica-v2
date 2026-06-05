<h2>Gestión de Permisos de Menú</h2>

<table>
    <tr>
        <th>Ítem de Menú</th>
        <th>Acciones</th>
    </tr>
    <?php foreach ($menu_items as $menu_item): ?>
        <tr>
            <td><?= $menu_item['name']; ?></td>
            <td>
                <a href="/permission_menu/edit/<?= $menu_item['id']; ?>">Gestionar Permisos</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
