<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Facturas con error
<?= $this->endSection() ?>

<?= $this->section('content-header') ?>
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">Facturas con error</h1>
    </div><!-- /.col -->
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Inicio</a></li>
            <li class="breadcrumb-item active">Facturas con error</li>
        </ol>
    </div><!-- /.col -->
</div><!-- /.row -->
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Facturas procesadas con error</h3>
    </div>
    <!-- /.card-header -->
    <div class="card-body p-0">
        <p class="px-3 pt-3 text-muted">Documentos con error de procesamiento o rechazo de Hacienda. Revisa la respuesta y corrige el documento para volver a enviarlo.</p>
        <table class="table table-striped" id="tablaSellos">
            <thead>
            <tr>
                <th style="width: 10px">#</th>
                <th>Tipo de Doc.</th>
                <th>Num. Interno ERP</th>
                <th>Num. Fact Electrónica </th>
                <th>Empresa</th>
                <th>Fecha</th>
                <th>Estado/Respuesta</th>
                <th>Accion</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
    <!-- /.card-body -->
    <!-- Modal Mensaje Error Ministerio Hacienda -->
    <div class="modal fade" id="modalMensaje" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modalMensajeLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMensajeLabel">Detalle del Mensaje</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <span style="font-weight: bold;">Estado:</span> <span id="estadoMensaje"></span><br>
                    <span style="font-weight: bold;">Observaciones: </span><span id="observacionMensaje"></span><br>
                    <span style="font-weight: bold;">Descripcion:</span> <span id="descripcionMensaje"></span><br>
                    <span style="font-weight: bold;">Mensaje MH:</span>

                    <pre id="mensajeReal">
                    </pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.card -->
<?= $this->endSection() ?>

<?= $this->section('js-page') ?>
<script >
    var url_base = '<?= base_url()  ?>';
</script>
<script src="js/facturas_procesadas.js"></script>
<?= $this->endSection() ?>






