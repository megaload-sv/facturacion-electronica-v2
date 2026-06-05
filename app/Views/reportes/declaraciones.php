<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Reportes
<?= $this->endSection() ?>

<?= $this->section('content-header') ?>
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">Reportes</h1>
    </div><!-- /.col -->
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Inicio</a></li>
            <li class="breadcrumb-item active">Reportes</li>
        </ol>
    </div><!-- /.col -->
</div><!-- /.row -->
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Reporte Declaraciones</h3>
  </div>

  <div class="card-body">
    <div class="row mb-3">
      <div class="col-md-3">
        <label>Fecha inicio</label>
        <input type="date" id="fecha_inicio" class="form-control">
      </div>
      <div class="col-md-3">
        <label>Fecha fin</label>
        <input type="date" id="fecha_fin" class="form-control">
      </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="incluir_anulados" value="1">
                <label class="form-check-label" for="incluir_anulados">
                    Agregar documentos anulados
                </label>
            </div>
        </div>
        <div class="col-md-6 d-flex align-items-end gap-2">
        <button class="btn btn-primary" id="btnConsultar">Consultar</button>
      </div>
    </div>

    <table id="tablaDeclaraciones" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>Tipo de Factura</th>
          <th>Fecha de Emisión</th>
          <th>Código de Generación</th>
          <th>Numero de control</th>
          <th>Sello Recibido</th>
          <th>NIT de Receptor</th>
          <th>NRC de Receptor</th>
          <th>Nombre de Cliente</th>
          <th>Monto sin IVA</th>
          <th>IVA</th>
          <th>Exento</th>
          <th>Valor de Retención</th>
          <th>Total de la Operación</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('js-page') ?>

<script >
    var url_base = '<?= base_url()  ?>';
</script>

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

<!-- Buttons -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>

<!-- Export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>


<script>
    let dt = null;

    $('#btnConsultar').on('click', function () {


        const url = buildUrl();

        if (dt) {
            dt.ajax.url(url).load();
            return;
        }

        dt = $('#tablaDeclaraciones').DataTable({
            processing: true,
            responsive: false,     // importante cuando usas scrollX con muchas columnas
            autoWidth: false,
            scrollX: true,
            scrollCollapse: true,
            ajax: {
                url: url,
                dataSrc: 'data'
            },
            dom:
                "<'row mb-2'" +
                "<'col-md-6 d-flex align-items-center'B>" +
                "<'col-md-6'f>" +
                ">" +
                "<'row'<'col-12'tr>>" +
                "<'row mt-2'" +
                "<'col-md-5'i>" +
                "<'col-md-7'p>" +
                ">",

            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn  btn-dte-excel btn-sm mr-2',
                    title: 'Declaraciones DTE',
                    filename: function () {
                        const inicio  = $('#fecha_inicio').val();
                        const fin     = $('#fecha_fin').val();
                        const incluir = $('#incluir_anulados').is(':checked') ? 'con_anulados' : 'sin_anulados';
                        return `declaraciones_${inicio}_${fin}_${incluir}`;
                    },
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csvHtml5',
                    text: '<i class="fas fa-file-csv"></i> CSV',
                    className: 'btn btn-dte-csv btn-sm',
                    title: 'Declaraciones DTE',
                    filename: function () {
                        const inicio  = $('#fecha_inicio').val();
                        const fin     = $('#fecha_fin').val();
                        const incluir = $('#incluir_anulados').is(':checked') ? 'con_anulados' : 'sin_anulados';
                        return `declaraciones_${inicio}_${fin}_${incluir}`;
                    },
                    exportOptions: {
                        columns: ':visible'
                    }
                }
            ],
            columns: [
                { data: 'tipo_factura' },
                { data: 'fechaEmision' },
                { data: 'codigoGeneracion' },
                { data: 'numeroControl' },
                { data: 'selloRecibido' },
                { data: 'nit_receptor' },
                { data: 'nrc_receptor' },
                { data: 'nombre_cliente' },
                { data: 'monto_sin_iva' },
                { data: 'iva' },
                { data: 'exento' },
                { data: 'retencion' },
                { data: 'total_operacion' }
            ]
        });

    });

    $('#incluir_anulados').on('change', function () {
        if (dt) dt.ajax.url(buildUrl()).load();
    });


    function buildUrl() {
        const inicio = $('#fecha_inicio').val();
        const fin    = $('#fecha_fin').val();
        const incluir = $('#incluir_anulados').is(':checked') ? 1 : 0;

        return `/reportes/declaraciones/data?fecha_inicio=${inicio}&fecha_fin=${fin}&incluir_anulados=${incluir}`;
    }


</script>
<?= $this->endSection() ?>
