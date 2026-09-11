<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Facturas
<?= $this->endSection() ?>

<?= $this->section('content-header') ?>
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">Facturas</h1>
    </div><!-- /.col -->
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Inicio</a></li>
            <li class="breadcrumb-item active">Facturas</li>
        </ol>
    </div><!-- /.col -->
</div><!-- /.row -->
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Listado de Facturas</h3>
    </div>
    <!-- /.card-header -->
    <div class="card-body p-0">
        <div id="paginationControls" style="float: right; padding: 5px;">
            <button id="prevPage" disabled class="btn btn-secondary ">Anterior</button>
            <span id="pagination">Página 1</span>
            <button id="nextPage" class="btn btn-secondary ">Siguiente</button>
        </div>
        <table class="table table-striped" id="facturasToMH">
            <thead>
            <tr>
                <th style="width: 10px">#</th>
                <th>Tipo de Doc.</th>
                <th>Num. Interno</th>
                <th>Empresa</th>
                <th>Codigo MH</th>
                <th>Fecha</th>
                <th>Accion</th>
                <!--<th>Estado/Respuesta</th>-->
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
    <!-- /.card-body -->
</div>
<!-- /.card -->
<?= $this->endSection() ?>

<?= $this->section('js-page') ?>
<script >
 var url_api = <?= json_encode($url_api, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
 var url_base = <?= json_encode(base_url(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="js/facturas.js"></script>
<?= $this->endSection() ?>




