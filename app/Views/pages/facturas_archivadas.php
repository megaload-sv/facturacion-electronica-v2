<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Facturas Archivadas
<?= $this->endSection() ?>

<?= $this->section('content-header') ?>
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">Facturas Archivadas</h1>
    </div><!-- /.col -->
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Inicio</a></li>
            <li class="breadcrumb-item active">Facturas Archivadas</li>
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

        <div class="card mb-3">
            <div class="card-header bg-light">
                <h3 class="card-title mb-0">
                    <i class="fas fa-filter"></i> Filtros de búsqueda
                </h3>
            </div>

            <div class="card-body">
                <div class="row">

                    <div class="col-md-3">
                        <label>Tipo de documento</label>
                        <select id="filtroTipoDTE" class="form-control">
                            <option value="">Todos</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Estado</label>
                        <select id="filtroEstadoDTE" class="form-control">
                            <option value="">Todos</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label>Fecha desde</label>
                        <input type="date" id="filtroFechaDesde" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label>Fecha hasta</label>
                        <input type="date" id="filtroFechaHasta" class="form-control">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" id="btnLimpiarFiltros" class="btn btn-secondary btn-block">
                            <i class="fas fa-eraser"></i> Limpiar
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <table class="table table-striped" id="tablaSellos">
            <thead>
            <tr>
                <th style="width: 10px">#</th>
                <th>Tipo de Doc.</th>
                <th>Num. Interno ERP</th>
                <th>Num. Fact Electrónica </th>
                <th>Empresa</th>
                <th>Fecha</th>
                <th>Nombre de Estado DTE</th>
                <th>Estado/Respuesta</th>
                <th>Correo</th>
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
    <!-- Modal Invalidacion dte -->
    <div class="modal fade" id="modalInvalidarDTE" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modalInvalidarDTELabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalInvalidarDTELabel">Detalle del DTE a Invalidar</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="" id="formInvalidDTE">
                        <input type="hidden" id="dteUUID" name="dteUUID" value="">
                        <input type="hidden" id="mhCode" name="mhc_document_type_id" value="">
                        <input type="hidden" id="transmission_datetime" value="">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label" for="mhc_invalidation_type_id">Motivo de la Invalidación</label>
                                    <select class="form-control" id="mhc_invalidation_type_id" name="mhc_invalidation_type_id">
                                        <option value="">-- Seleccionar --</option>
                                        <?php
                                        foreach ($catTipoInvalidacion as $item) {
                                            $selected = "";
                                            echo "<option  value='" . $item["codigo"] . "' $selected>" . $item["valores"] . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row" id="rowCodeGenerationR">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label" for="code_generation_r">Nuevo DTE</label>
                                    <select class="form-control" name="code_generation_r" id="code_generation_r" style="width: 100%">

                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label" for="issuer_name">Nombre responsable de la invalidación</label>
                                    <input type="text" id="issuer_name" name="issuer_name" class="form-control" minlength="1"
                                           maxlength="250" value=""/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label" for="issuer_document_type">Tipo documento de identificación del responsable</label>
                                    <select class="form-control" id="issuer_document_type" name="issuer_document_type">
                                        <option value="">-- Seleccionar --</option>
                                        <?php
                                        foreach ($catTipoDocumentoReceptor as $item) {
                                            $selected = "";//str_to_upper($item["code"]) === "TGA-36" ? "selected" : "";
                                            echo "<option  value='" . $item["codigo"] . "' $selected>" . $item["valores"] . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label" for="issuer_document_number">Número de documento de identificación del responsable</label>
                                    <input type="text" id="issuer_document_number" name="issuer_document_number" class="form-control" minlength="3"
                                           maxlength="20" value=""/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label" for="receiver_name">Nombre responsable del solicitante</label>
                                    <input type="text" id="receiver_name" name="receiver_name" class="form-control" minlength="1"
                                           maxlength="250" value=""/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label" for="mhc_receiver_document_type_id">Tipo documento de identificación del solicitante</label>
                                    <select class="form-control" id="mhc_receiver_document_type_id" name="mhc_receiver_document_type_id">
                                        <option value="">-- Seleccionar --</option>
                                        <?php
                                        foreach ($catTipoDocumentoReceptor as $item) {
                                            $selected = "";//str_to_upper($item["code"]) === "TGA-36" ? "selected" : "";
                                            echo "<option  value='" . $item["codigo"] . "' $selected>" . $item["valores"] . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label" for="receiver_document_number">Número de documento de identificación del solicitante</label>
                                    <input type="text" id="receiver_document_number" name="receiver_document_number" class="form-control" minlength="3"
                                           maxlength="20" value=""/>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-success processInvalidarDTE">Invalidar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Reenvio de correo -->
    <div class="modal fade" id="modalReenvioCorreo" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modalReenvioCorreoLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMensajeLabel">Reenvio de correo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="" id="reenvioCorreo">
                        <input type="hidden" id="dteUUID" name="dteUUID" value="">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label" for="correo">Correo</label>
                                    <input type="text" id="correoReenvio" name="correoReenvio" class="form-control" minlength="1"
                                           maxlength="250" value=""/>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-success reenviarCorreoFrm">Enviar Correo</button>
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
<script src="js/facturas_archivadas.js"></script>
<?= $this->endSection() ?>






