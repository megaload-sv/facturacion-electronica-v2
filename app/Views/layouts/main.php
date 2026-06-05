<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->renderSection('title') ?></title>

    <meta name="theme-color" content="#1a1bb3">

    <link rel="icon" type="image/x-icon" href="<?= base_url('images/favicon.ico') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('images/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('images/favicon-16x16.png') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('images/favicon-32x32.png') ?>">



    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= base_url('plugins/fontawesome-free/css/all.min.css') ?>">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet"
          href="<?= base_url('plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') ?>">
    <!-- iCheck -->
    <link rel="stylesheet" href="<?= base_url('plugins/icheck-bootstrap/icheck-bootstrap.min.css') ?>">
    <!-- JQVMap -->
    <link rel="stylesheet" href="<?= base_url('plugins/jqvmap/jqvmap.min.css') ?>">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= base_url('dist/css/adminlte.min.css') ?>">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="<?= base_url('plugins/overlayScrollbars/css/OverlayScrollbars.min.css') ?>">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="<?= base_url('plugins/daterangepicker/daterangepicker.css') ?>">
    <!-- summernote -->
    <link rel="stylesheet" href="<?= base_url('plugins/summernote/summernote-bs4.min.css') ?>">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <!-- datatable -->
    <link rel="stylesheet" href="<?= base_url('plugins/datatables-bs4/css/dataTables.bootstrap4.css') ?>">

    <style>
        /* Estilo del select2 solo para la clase custom-select2 */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #007bff; /* Azul */
            border-color: #007bff; /* Borde del mismo color */
            color: white; /* Texto blanco */
            padding: 5px;
            border-radius: 4px;
            margin-right: 4px;
            padding-left: 22px;
        }

        /* Cambiar el color del icono de cierre (x) */
        .custom-select2 .select2-selection__choice__remove {
            color: white; /* Blanco para que resalte sobre el fondo azul */
            margin-left: 5px;
        }

        /* Cambiar el hover del icono de cierre */
        .custom-select2 .select2-selection__choice__remove:hover {
            color: #ffcccc; /* Un tono más claro al pasar el ratón */
        }

        .fila-inactiva {
            background-color: #f2f2f2 !important;
            color: #888;
        }

        .btn-dte-excel {
            background-color: #1d6f42;
            border-color: #1d6f42;
            color: #fff;
        }

        .btn-dte-excel:hover {
            background-color: #155c34;
            border-color: #155c34;
            color: #fff;
        }

        .btn-dte-csv {
            background-color: #0b7285;
            border-color: #0b7285;
            color: #fff;
        }

        .btn-dte-csv:hover {
            background-color: #095c6b;
            border-color: #095c6b;
            color: #fff;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__shake" src="<?= base_url('dist/img/logo_megaload.webp') ?>" alt="Megaload" height="60"
             width="60">
    </div>

    <?= $this->include('templates/header') ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <?= $this->renderSection('content-header') ?>
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?= $this->renderSection('content') ?>
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
    <?= $this->include('templates/footer') ?>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="<?= base_url('plugins/jquery/jquery.min.js') ?>"></script>
<!-- jQuery UI 1.11.4 -->
<script src="<?= base_url('plugins/jquery-ui/jquery-ui.min.js') ?>"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
    $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="<?= base_url('plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<!-- ChartJS -->
<script src="<?= base_url('plugins/chart.js/Chart.min.js') ?>"></script>
<!-- Sparkline -->
<script src="<?= base_url('plugins/sparklines/sparkline.js') ?>"></script>
<!-- JQVMap -->
<script src="<?= base_url('plugins/jqvmap/jquery.vmap.min.js') ?>"></script>
<script src="<?= base_url('plugins/jqvmap/maps/jquery.vmap.usa.js') ?>"></script>
<!-- jQuery Knob Chart -->
<script src="<?= base_url('plugins/jquery-knob/jquery.knob.min.js') ?>"></script>
<!-- daterangepicker -->
<script src="<?= base_url('plugins/moment/moment.min.js') ?>"></script>
<script src="<?= base_url('plugins/daterangepicker/daterangepicker.js') ?>"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="<?= base_url('plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') ?>"></script>
<!-- Summernote -->
<script src="<?= base_url('plugins/summernote/summernote-bs4.min.js') ?>"></script>
<!-- overlayScrollbars -->
<script src="<?= base_url('plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') ?>"></script>
<!-- AdminLTE App -->
<script src="<?= base_url('dist/js/adminlte.min.js') ?>"></script>
<!-- BlockUI -->
<script src="<?= base_url('plugins/jquery-block-ui/jquery.blockUI.js') ?>"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Bootstrap JS (Debe cargarse después de jQuery) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery Validation Plugin -->
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>

<!-- Datatable -->
<script src="<?= base_url('plugins/datatables/jquery.dataTables.js') ?>"></script>
<script src="<?= base_url('plugins/datatables-bs4/js/dataTables.bootstrap4.js') ?>"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?= $this->renderSection('js-page') ?>

</body>
</html>
<script>
    $(document).ready(function () {
        $('#roles').select2({
            placeholder: "Selecciona roles",
            allowClear: true
        });
    });
</script>

