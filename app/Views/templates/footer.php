<footer class="main-footer">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <strong>
                &copy; <?= date('Y') ?>
                <a href="https://grupomegaload.com" target="_blank">Grupo Megaload</a>.
            </strong>
            Todos los derechos reservados.
        </div>

        <!-- Derecha -->
        <div class="float-right d-none d-md-inline-block text-center">
            <span class="badge badge-<?= ENVIRONMENT === 'production' ? 'success' : 'warning' ?>">
                APP: <?= esc(strtoupper(ENVIRONMENT)) ?> / DTE: <?= esc(strtoupper(config(\Config\Dte::class)->selectedEnvironment())) ?>
            </span>
            <span class="ml-2">
                <b>Versión</b> <?= env('APP_VERSION', '1.0.0') ?>
            </span>
        </div>

        <div class="float-right d-none d-md-inline-block">
            <span class="badge badge-secondary">
                Sistema de Facturación Electrónica
            </span>
        </div>
    </div>
</footer>
