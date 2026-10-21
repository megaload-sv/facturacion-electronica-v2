(() => {
    'use strict';
    const error = document.getElementById('access-errors');
    if (error) error.focus();
    const summary = document.getElementById('access-summary');
    if (!summary) return;
    const mapping = JSON.parse(document.getElementById('access-role-permissions').textContent);
    const roles = [...document.querySelectorAll('[name="role_ids[]"]')];
    const permissions = [...document.querySelectorAll('[name="permission_ids[]"]')];
    const update = () => {
        const selected = roles.filter(role => role.checked);
        const admin = selected.some(role => role.dataset.admin === '1');
        const ids = new Set(selected.map(role => role.value));
        const inherited = new Set(mapping.filter(row => ids.has(String(row.role_id))).map(row => String(row.permission_id)));
        summary.replaceChildren();
        const list = document.createElement('ul');
        permissions.forEach(permission => {
            const fromRole = admin || inherited.has(permission.value);
            document.querySelector(`[data-inherited="${permission.value}"]`).hidden = !fromRole;
            if (fromRole || permission.checked) {
                const item = document.createElement('li');
                item.textContent = permission.dataset.label;
                const source = document.createElement('small');
                source.textContent = fromRole ? (permission.checked ? 'Rol + permiso adicional' : 'Heredado del rol') : 'Permiso adicional';
                item.append(source);
                list.append(item);
            }
        });
        if (admin) {
            const note = document.createElement('p');
            note.className = 'access-tag';
            note.textContent = 'Acceso completo · Administración incluida';
            summary.append(note);
        }
        if (list.children.length) summary.append(list);
        else if (!admin) {
            const empty = document.createElement('p');
            empty.className = 'text-muted';
            empty.textContent = 'Sin permisos asignados. Seleccione un rol o agregue permisos.';
            summary.append(empty);
        }
    };
    [...roles, ...permissions].forEach(input => input.addEventListener('change', update));
    update();
})();
