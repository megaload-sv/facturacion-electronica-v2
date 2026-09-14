<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión | Megaload</title>

    <link rel="icon" type="image/x-icon" href="<?= base_url('images/favicon.ico') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('images/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('images/favicon-16x16.png') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('images/favicon-32x32.png') ?>">

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?= base_url('plugins/fontawesome-free/css/all.min.css') ?>">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="<?= base_url('plugins/icheck-bootstrap/icheck-bootstrap.min.css') ?>">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?= base_url('dist/css/adminlte.min.css') ?>">
  <link rel="stylesheet" href="<?= base_url('css/megaload.css') ?>?v=20260914">
</head>
<body class="hold-transition login-page megaload-app ml-login">
<div class="login-box">
  <div class="login-logo">
    <a href="<?= base_url('login') ?>"><img src="<?= base_url('images/logo_megaload_only_img.png') ?>" alt="" width="42"> <strong>MEGALOAD</strong></a><span class="ml-login-subtitle">FACTURACIÓN ELECTRÓNICA</span>
  </div>
  <!-- /.login-logo -->
  <div class="card">
    <div class="card-body login-card-body">
      <h1 class="ml-login-heading">Bienvenido de nuevo</h1><p class="login-box-msg">Ingresa tus credenciales para continuar.</p>
        <?php if(session()->getFlashdata('error')): ?>
            <p style="color:red; text-align: center;"><?= esc(session()->getFlashdata('error')) ?></p>
        <?php endif; ?>
      <form action="<?= base_url('login/authenticate') ?>" method="post">
<?= csrf_field() ?>
        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Usuario" aria-label="Usuario" autocomplete="username" required name="username"/>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" class="form-control" placeholder="Contraseña" aria-label="Contraseña" autocomplete="current-password" required name="password"/>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row justify-content-center">
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-block">Iniciar sesión</button>
          </div>
          <!-- /.col -->
        </div>
      </form>

      <p class="mb-1">
        <span class="ml-login-help">Si necesitas recuperar el acceso, contacta al administrador.</span>
      </p>
    </div>
    <!-- /.login-card-body -->
  </div>
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="<?= base_url('plugins/jquery/jquery.min.js') ?>"></script>
<!-- Bootstrap 4 -->
<script src="<?= base_url('plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<!-- AdminLTE App -->
<script src="<?= base_url('dist/js/adminlte.min.js') ?>"></script>
</body>
</html>
