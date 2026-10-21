<div class="access-options">
<?php foreach ($permissions as $permission): ?>
<label class="access-option"><input type="checkbox" name="permission_ids[]" value="<?= (int) $permission['id'] ?>" data-label="<?= esc($permission['description'] ?: $permission['permission_name']) ?>" <?= in_array((int) $permission['id'], $selectedPermissions, true) ? 'checked' : '' ?>><span><strong><?= esc($permission['description'] ?: $permission['permission_name']) ?></strong><small><?= esc($permission['permission_name']) ?></small><small class="access-inherited" data-inherited="<?= (int) $permission['id'] ?>" hidden>Incluido en el rol seleccionado</small></span></label>
<?php endforeach ?>
</div>
<?php if (!$permissions): ?><p class="text-muted">No hay permisos disponibles en el catálogo.</p><?php endif ?>
