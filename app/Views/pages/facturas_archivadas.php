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
<style>
    .archive-toolbar { padding: .85rem 1rem .65rem; border-bottom: 1px solid #e2e8f0; background: #fafbfc; }
    .archive-filters { display: grid; grid-template-columns: minmax(115px, .8fr) minmax(170px, 1.5fr) minmax(125px, 1fr) repeat(2, minmax(145px, 1fr)) auto; gap: .75rem; align-items: end; }
    .archive-filters label { display: block; margin-bottom: .25rem; font-size: .75rem; font-weight: 600; color: #64748b; }
    .archive-filters .form-control { width: 100%; border-color: #d8dee6; border-radius: .375rem; font-size: .875rem; }
    .archive-filters .btn { white-space: nowrap; border-radius: .375rem; }
    .archive-summary { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem .75rem; margin-top: .5rem; font-size: .75rem; color: #64748b; }
    .archive-table { padding: .65rem 1rem 0; }
    @media (max-width: 1199.98px) { .archive-filters { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 575.98px) { .archive-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; } .archive-toolbar { padding: .75rem; } .archive-table { padding: .5rem .75rem 0; } }
    #tablaSellos tbody td { vertical-align: middle; }
    #tablaSellos .dte-actions { display: flex; align-items: center; gap: .5rem; white-space: nowrap; }
    #tablaSellos .dte-actions .btn { border-radius: .5rem; padding: .4rem .7rem; }
    #tablaSellos .dte-actions-toggle { background: #fff; border: 1px solid #cbd5e1; color: #334155; }
    #tablaSellos .dte-actions-toggle:hover,
    #tablaSellos .dte-actions-toggle[aria-expanded="true"] { background: #f1f5f9; border-color: #94a3b8; }
    #tablaSellos .dte-actions-menu { min-width: 14rem; padding: .4rem; border: 1px solid #e2e8f0; border-radius: .65rem; box-shadow: 0 .5rem 1.5rem rgba(15, 23, 42, .12); }
    #tablaSellos .dte-actions-menu .dropdown-item { padding: .65rem .75rem; border-radius: .35rem; }
    #tablaSellos .dte-actions-menu .dropdown-item:focus { outline: 2px solid #1a1bb3; outline-offset: -2px; }
    #tablaSellos .dte-actions-menu .text-danger:hover,
    #tablaSellos .dte-actions-menu .text-danger:focus { background: #fef2f2; color: #b91c1c !important; }
    #tablaSellos .dte-actions .btn:focus { outline: 2px solid #1a1bb3; outline-offset: 2px; }
</style>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Facturas aceptadas por Hacienda</h3>
    </div>
    <!-- /.card-header -->
    <div class="card-body p-0">
        <div class="archive-toolbar" role="group" aria-label="Filtros de documentos archivados">
            <div class="archive-filters">
                <div>
                    <label for="filtroAnioArchivo">Año de emisión</label>
                    <select id="filtroAnioArchivo" class="form-control form-control-sm" aria-describedby="ayudaAnioArchivo">
                        <?php foreach ($aniosArchivo as $anio): ?>
                            <option value="<?= esc($anio) ?>" <?= $anio === $anioActual ? 'selected' : '' ?>><?= esc($anio) ?><?= $anio === $anioActual ? ' (actual)' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                    <div>
                        <label for="filtroTipoDTE">Tipo de documento</label>
                        <select id="filtroTipoDTE" class="form-control form-control-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>

                    <div>
                        <label for="filtroEstadoDTE">Estado</label>
                        <select id="filtroEstadoDTE" class="form-control form-control-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>

                    <div>
                        <label for="filtroFechaDesde">Desde</label>
                        <input type="date" id="filtroFechaDesde" class="form-control form-control-sm">
                    </div>

                    <div>
                        <label for="filtroFechaHasta">Hasta</label>
                        <input type="date" id="filtroFechaHasta" class="form-control form-control-sm">
                    </div>

                    <div>
                        <button type="button" id="btnLimpiarFiltros" class="btn btn-sm btn-outline-secondary" title="Limpiar filtros del año seleccionado">
                            <i class="fas fa-eraser" aria-hidden="true"></i> Limpiar
                        </button>
                    </div>

            </div>
            <div class="archive-summary">
                <span id="estadoCargaArchivo" role="status" aria-live="polite"></span>
                <span id="ayudaAnioArchivo">Filtros por año de emisión · Incluye documentos anulados.</span>
                <button type="button" id="btnReintentarArchivo" class="btn btn-sm btn-outline-primary" hidden>Reintentar carga</button>
            </div>
        </div>

        <div class="archive-table">
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
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
        </div>
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
<?= csrf_field() ?>
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
<?= csrf_field() ?>
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
<script src="<?= base_url('js/facturas_archivadas.js') ?>?v=<?= filemtime(FCPATH . 'js/facturas_archivadas.js') ?>"></script>
<?= $this->endSection() ?>






