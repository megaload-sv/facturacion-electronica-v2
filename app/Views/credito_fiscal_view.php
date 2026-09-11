<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pruebas Masivas DTE</title>
    <!-- Bootstrap 5 CSS desde CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- jQuery desde CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="container py-4">
<div class="container mt-4">
    <h3>🚀 Pruebas Masivas - DTE Crédito Fiscal (03)</h3>
    <form id="formCreditoFiscal" class="row g-3">
<?= csrf_field() ?>
        <div class="col-md-3">
            <label for="inicio" class="form-label">Correlativo Inicial</label>
            <input type="number" class="form-control" id="inicio" name="inicio" value="1" min="1">
        </div>
        <div class="col-md-3">
            <label for="total" class="form-label">Cantidad de Pruebas</label>
            <input type="number" class="form-control" id="total" name="total" value="3" min="1">
        </div>
        <div class="col-md-3">
            <label for="intervalo" class="form-label">Intervalo (segundos)</label>
            <input type="number" class="form-control" id="intervalo" name="intervalo" value="5" min="1">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">🚀 Iniciar</button>
            <button type="button" class="btn btn-success me-2" id="btnAuto">▶ Auto Ejecutar</button>
            <button type="button" class="btn btn-danger" id="btnStop" disabled>■ Detener</button>
        </div>
    </form>

    <div id="progreso" class="mt-4" style="display:none">
        <h5>Progreso:</h5>
        <div id="estadoActual" class="mb-2">
            <strong>🧪 Ejecutando prueba <span id="pruebaActual">0</span> de <span id="pruebaTotal">0</span></strong>
        </div>
        <div class="progress">
            <div id="barraProgreso" class="progress-bar" role="progressbar" style="width: 0%">0%</div>
        </div>
    </div>

    <div id="resultados" class="mt-4"></div>
</div>

<script>
    let intervaloID = null;
    let actual = 0;
    let total = 0;
    let intervaloSegundos = 5;
    let correlativo = 1;
    let ejecucionActiva = false;

    function ejecutarPruebaCF() {
        if (!ejecucionActiva) return;
        if (actual >= total) {
            detenerAutoEjecucion();
            $('#resultados').prepend('<div class="alert alert-success">✅ Pruebas finalizadas.</div>');
            return;
        }

        $('#pruebaActual').text(actual + 1);
        $('#pruebaTotal').text(total);
        $('#progreso').show();
        $('#barraProgreso').css('width', '0%').text('0%');

        $.getJSON(`<?= base_url('dte/generarCreditoFiscal') ?>`, { inicio: correlativo, total: 1 }, function (response) {
            const dte = response[0].dte;
            const firma = response[0].firma;
            const sello = response[0].sello;

            let porcentaje = Math.round(((actual + 1) / total) * 100);
            $('#barraProgreso').css('width', porcentaje + '%').text(porcentaje + '%');

            const html = `
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <strong>DTE Crédito Fiscal #${actual + 1}</strong> - ${dte.identificacion.numeroControl}
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>📄 JSON DTE</h6>
                            <pre class="bg-light p-2 border rounded">${JSON.stringify(dte, null, 2)}</pre>
                        </div>
                        <div class="col-md-4">
                            <h6>🔏 Firma</h6>
                            <pre class="bg-light p-2 border rounded">${JSON.stringify(firma, null, 2)}</pre>
                        </div>
                        <div class="col-md-4">
                            <h6>📬 Sello MH</h6>
                            <pre class="bg-light p-2 border rounded">${JSON.stringify(sello, null, 2)}</pre>
                        </div>
                    </div>
                </div>
            </div>
        `;

            $('#resultados').prepend(html);
            actual++;
            correlativo++;
        });
    }

    function detenerAutoEjecucion() {
        clearInterval(intervaloID);
        intervaloID = null;
        ejecucionActiva = false;
        $('#btnAuto').prop('disabled', false);
        $('#btnStop').prop('disabled', true);
    }

    $('#formCreditoFiscal').on('submit', function (e) {
        e.preventDefault();
        total = parseInt($('#total').val());
        actual = 0;
        correlativo = parseInt($('#inicio').val());
        ejecucionActiva = true;
        ejecutarPruebaCF();
    });

    $('#btnAuto').on('click', function () {
        total = parseInt($('#total').val());
        actual = 0;
        correlativo = parseInt($('#inicio').val());
        intervaloSegundos = parseInt($('#intervalo').val());
        ejecucionActiva = true;

        $('#btnAuto').prop('disabled', true);
        $('#btnStop').prop('disabled', false);
        ejecutarPruebaCF();
        intervaloID = setInterval(ejecutarPruebaCF, intervaloSegundos * 1000);
    });

    $('#btnStop').on('click', detenerAutoEjecucion);
</script>


</body>
</html>
