(() => {
    'use strict';
    const titles = { success: 'Operación completada', error: 'Error', warning: 'Atención', info: 'Información' };
    window.AppAlerts = {
        notify(message, icon = 'info') {
            return Swal.fire({ title: titles[icon], text: String(message), icon, confirmButtonText: 'Aceptar' });
        },
        async confirm(message) {
            const result = await Swal.fire({ title: 'Confirmar acción', text: message, icon: 'warning',
                showCancelButton: true, confirmButtonText: 'Sí, continuar', cancelButtonText: 'Cancelar', focusCancel: true });
            return result.isConfirmed;
        }
    };
    const pending = new WeakSet();
    const approved = new WeakSet();
    document.addEventListener('submit', async event => {
        const form = event.target;
        if (!form.matches('form[data-confirm]')) return;
        if (approved.has(form)) { approved.delete(form); return; }
        event.preventDefault();
        if (pending.has(form)) return;
        pending.add(form);
        try {
            if (await AppAlerts.confirm(form.dataset.confirm)) {
                approved.add(form);
                try { form.requestSubmit(event.submitter || undefined); }
                finally { approved.delete(form); }
            }
        } finally { pending.delete(form); }
    });
    document.addEventListener('DOMContentLoaded', async () => {
        const payload = document.getElementById('app-flash-messages');
        if (!payload) return;
        for (const message of JSON.parse(payload.textContent)) {
            await AppAlerts.notify(message.text, message.icon);
        }
    });
})();
