<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
    Sistema de Facturacion Electronica | Dashboard
<?= $this->endSection() ?>

<?= $this->section('user-info'); ?>
    <!-- Sidebar user panel (optional) -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
            <img src="dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
            <a href="#" class="d-block"><?= session()->get('username'); ?></a>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('content-header') ?>
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0">Dashboard</h1>
        </div><!-- /.col -->
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </div><!-- /.col -->
    </div><!-- /.row -->
<?= $this->endSection() ?>

<?= $this->section('content') ?>

    <!-- Small boxes (Stat box) -->
    <div class="row">
        <!-- ./col -->
        <div class="col-lg-4 col-6">
            <!-- small box -->
            <div class="small-box bg-success">
                <div class="inner">
                    <h3><?= $facturasProcesadasOK ?></h3>

                    <p>Facturas procesadas</p>
                </div>
                <div class="icon">
                    <i class="ion ion-stats-bars"></i>
                </div>
                <a href="procesadas-archivadas" class="small-box-footer">Ver detalle <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-4 col-6">
            <!-- small box -->
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3><?= $facturasProcesadasError ?></h3>

                    <p>Facturas procesadas con error</p>
                </div>
                <div class="icon">
                    <i class="ion ion-person-add"></i>
                </div>
                <a href="facturas-procesadas" class="small-box-footer">Ver detalle <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-4 col-6">
            <!-- small box -->
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3><?= $facturasProcesadasPendientes ?></h3>

                    <p>Facturas pendientes de enviar</p>
                </div>
                <div class="icon">
                    <!--<i class="ion ion-pie-graph"></i>-->
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <a href="/facturas" class="small-box-footer">Ver detalle <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
    </div>
    <!-- /.row -->


<?= $this->endSection() ?>