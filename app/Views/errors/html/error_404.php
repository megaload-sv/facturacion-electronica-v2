<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 | Ups… esta página no existe</title>

    <!-- Si AdminLTE ya se carga globalmente en tu layout, podés quitar estas 3 líneas -->
    <link rel="stylesheet" href="<?= base_url('plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('dist/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('plugins/icheck-bootstrap/icheck-bootstrap.min.css') ?>">

    <style>
        .crane-wrap { max-width: 720px; margin: 0 auto; }
        .crane-card { border-radius: 16px; overflow: hidden; }
        .crane-svg { width: 100%; height: auto; display:block; background: linear-gradient(180deg,#f8f9fa,#ffffff); }
        .badge-fun { font-size: 0.9rem; }
    </style>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <div class="content-wrapper" style="margin-left:0;">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-12">
                    <div class="col-sm-12">
                        <h1 style="text-align: center">Error 404</h1>

                        <ol class="breadcrumb float-sm-none">
                            <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Inicio</a></li>
                            <li class="breadcrumb-item active">404</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid crane-wrap">
                <div class="card shadow crane-card">
                    <div class="card-body">

                        <div class="d-flex align-items-center mb-3">
                            <span class="badge badge-warning badge-fun mr-2"><i class="fas fa-hard-hat"></i> Zona en mantenimiento</span>
                            <span class="text-muted">No encontramos la ruta que buscabas.</span>
                        </div>

                        <h2 class="mb-2">
                            <b>¡Ups!</b> La página se fue a la obra… y la grúa se cayó 😅
                        </h2>

                        <p class="text-muted mb-4">
                            Parece que la URL está mal escrita, fue movida o nunca existió.
                            No te preocupés: el sistema sigue vivo… solo que esta ruta no.
                        </p>

                        <!-- “Imagen” (SVG) de grúa caída -->
                        <svg class="crane-svg" viewBox="0 0 900 320" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Grúa caída estilo ilustración">
                            <!-- suelo -->
                            <rect x="0" y="250" width="900" height="70" fill="#e9ecef"/>
                            <rect x="0" y="245" width="900" height="8" fill="#ced4da"/>
                            <!-- nube -->
                            <g opacity="0.35">
                                <ellipse cx="150" cy="70" rx="55" ry="25" fill="#adb5bd"/>
                                <ellipse cx="195" cy="70" rx="45" ry="22" fill="#adb5bd"/>
                                <ellipse cx="115" cy="72" rx="35" ry="18" fill="#adb5bd"/>
                            </g>

                            <!-- grúa caída -->
                            <g transform="translate(120,30) rotate(-14 300 160)">
                                <!-- base -->
                                <rect x="140" y="205" width="220" height="30" rx="6" fill="#343a40"/>
                                <circle cx="175" cy="240" r="14" fill="#495057"/>
                                <circle cx="325" cy="240" r="14" fill="#495057"/>

                                <!-- torre -->
                                <rect x="235" y="70" width="30" height="140" fill="#ffc107"/>
                                <g stroke="#e0a800" stroke-width="6">
                                    <line x1="235" y1="90" x2="265" y2="115"/>
                                    <line x1="235" y1="115" x2="265" y2="140"/>
                                    <line x1="235" y1="140" x2="265" y2="165"/>
                                    <line x1="235" y1="165" x2="265" y2="190"/>
                                </g>

                                <!-- brazo -->
                                <rect x="260" y="70" width="320" height="22" rx="6" fill="#ffc107"/>
                                <rect x="560" y="58" width="30" height="46" rx="6" fill="#ffca2c"/>

                                <!-- cable y gancho -->
                                <line x1="540" y1="92" x2="540" y2="165" stroke="#6c757d" stroke-width="4"/>
                                <path d="M540 165 c0 20 35 20 35 0" fill="none" stroke="#6c757d" stroke-width="6"/>
                                <circle cx="540" cy="165" r="6" fill="#6c757d"/>

                                <!-- “caída” (grieta) -->
                                <path d="M220 235 L245 260 L265 250 L290 280" stroke="#adb5bd" stroke-width="6" fill="none" opacity="0.7"/>
                            </g>

                            <!-- cono -->
                            <g transform="translate(640,185)">
                                <path d="M40 0 L70 80 L10 80 Z" fill="#fd7e14"/>
                                <rect x="10" y="52" width="60" height="10" fill="#fff"/>
                                <rect x="16" y="78" width="48" height="10" fill="#343a40"/>
                            </g>

                            <!-- texto grande 404 -->
                            <text x="560" y="140" font-size="84" font-family="Arial, sans-serif" fill="#212529" font-weight="700">404</text>
                            <text x="560" y="175" font-size="22" font-family="Arial, sans-serif" fill="#6c757d">Ruta no encontrada</text>
                        </svg>

                        <div class="mt-4 d-flex flex-wrap" style="gap:10px;">
                            <a href="<?= base_url('/') ?>" class="btn btn-primary">
                                <i class="fas fa-home mr-1"></i> Volver al inicio
                            </a>
                            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                                <i class="fas fa-undo mr-1"></i> Regresar
                            </a>
                            <a href="<?= base_url('/dashboard') ?>" class="btn btn-outline-success">
                                <i class="fas fa-tachometer-alt mr-1"></i> Ir al Dashboard
                            </a>
                        </div>

                    </div>
                </div>

                <p class="text-center text-muted mt-3 mb-0">
                    Consejo amistoso: si ves esto muy seguido, decile al dev que revise las rutas 🧯😄
                </p>
            </div>
        </section>
    </div>
</div>

<!-- Si tu AdminLTE ya carga JS globalmente, podés omitir esto -->
<script src="<?= base_url('plugins/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('dist/js/adminlte.min.js') ?>"></script>
</body>
</html>
