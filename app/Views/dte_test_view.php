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

<h3 class="mb-4">🔄 Generar DTEs - Pruebas Masivas</h3>

<form id="formTest" class="row g-3">
<?= csrf_field() ?>
    <div class="col-md-3">
        <label for="inicio" class="form-label">Correlativo Inicial</label>
        <input type="number" class="form-control" id="inicio" name="inicio" value="1" required>
    </div>
    <div class="col-md-3">
        <label for="total" class="form-label">Cantidad de Pruebas</label>
        <input type="number" class="form-control" id="total" name="total" value="5" required>
    </div>
    <div class="col-md-3">
        <label for="intervalo" class="form-label">Intervalo (segundos)</label>
        <input type="number" class="form-control" id="intervalo" name="intervalo" value="10" min="1">
    </div>
    <div class="col-md-3 align-self-end">
        <button type="button" class="btn btn-success me-2" id="btnAuto">▶ Auto Ejecutar</button>
        <button type="button" class="btn btn-danger" id="btnStop" disabled>■ Detener</button>
    </div>
    <div class="col-md-3 align-self-end">
        <button type="submit" class="btn btn-primary">🚀 Iniciar</button>
    </div>
</form>

<div class="my-4" id="progreso" style="display:none;">
    <h5>Progreso:</h5>
    <div class="progress">
        <div id="barraProgreso" class="progress-bar progress-bar-striped bg-success" role="progressbar"
             style="width: 0%">
            0%
        </div>
        <div id="estadoActual" class="mb-2">
            <strong>🧪 Ejecutando prueba <span id="pruebaActual">0</span> de <span id="pruebaTotal">0</span></strong>
        </div>
    </div>
</div>

<div id="resultados" class="mt-5"></div>

<script>
    $(function () {
        $('#formTest').on('submit', function (e) {
            e.preventDefault();
            let inicio = $('#inicio').val();
            let total = $('#total').val();
            $('#progreso').show();
            $('#barraProgreso').css('width', '0%').text('0%');
            $('#resultados').html('<p>Procesando...</p>');

            $.getJSON(`<?= base_url('dte/generar') ?>`, {inicio, total}, function (response) {
                const total = response.cantidadGenerada;
                const dtes = response.dtes;
                const firmas = response.firmas;
                let html = '';

                dtes.forEach((dte, index) => {
                    let porcentaje = Math.round(((index + 1) / total) * 100);
                    $('#barraProgreso').css('width', porcentaje + '%').text(porcentaje + '%');

                    html += `
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong>DTE #${index + 1}</strong> - ${dte.identificacion.numeroControl}
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>📄 JSON DTE Generado</h6>
                                <pre class="bg-light p-2 rounded border">${JSON.stringify(dte, null, 2)}</pre>
                            </div>
                            <div class="col-md-4">
                                <h6>🔏 Resultado de Firma</h6>
                                <pre class="bg-light p-2 rounded border">${JSON.stringify(firmas[index], null, 2)}</pre>
                            </div>
                            <div class="col-md-4">
                                <h6>📬 Resultado de Sello (MH)</h6>
                                <pre class="bg-light p-2 rounded border">${JSON.stringify(response[index].sello, null, 2)}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            `;
                });

                $('#resultados').html(html);
            }).fail(function (xhr, status, error) {
                $('#resultados').html('<div class="alert alert-danger">❌ Error en el servidor: ' + error + '</div>');
            });
        });

        let intervaloID = null;
        let actual = 0;
        let total = 0;
        let intervaloSegundos = 10;
        let correlativo = 1;
        let ejecucionActiva = false;

        function ejecutarPruebaIndividual() {
            if (!ejecucionActiva) return;

            if (actual >= total) {
                detenerAutoEjecucion();
                $('#resultados').prepend('<div class="alert alert-success">✅ Pruebas finalizadas automáticamente.</div>');
                return;
            }

            $('#pruebaActual').text(actual + 1);
            $('#pruebaTotal').text(total);
            $('#progreso').show();
            $('#barraProgreso').css('width', '0%').text('0%');

            $.getJSON(`<?= base_url('dte/generar') ?>`, { inicio: correlativo, total: 1 }, function (response) {
                const dte = response[0].dte;
                const firma = response[0].firma;
                const sello = response[0].sello;

                let porcentaje = Math.round(((actual + 1) / total) * 100);
                $('#barraProgreso').css('width', porcentaje + '%').text(porcentaje + '%');

                const html = `
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <strong>DTE #${actual + 1}</strong> - ${dte.identificacion.numeroControl}
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>📄 JSON DTE Generado</h6>
                            <pre class="bg-light p-2 rounded border">${JSON.stringify(dte, null, 2)}</pre>
                        </div>
                        <div class="col-md-4">
                            <h6>🔏 Resultado de Firma</h6>
                            <pre class="bg-light p-2 rounded border">${JSON.stringify(firma, null, 2)}</pre>
                        </div>
                        <div class="col-md-4">
                            <h6>📬 Resultado de Sello (MH)</h6>
                            <pre class="bg-light p-2 rounded border">${JSON.stringify(sello, null, 2)}</pre>
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
            if (intervaloID !== null) {
                clearInterval(intervaloID);
                intervaloID = null;
            }
            ejecucionActiva = false;
            $('#btnAuto').prop('disabled', false);
            $('#btnStop').prop('disabled', true);
        }

        $('#btnAuto').on('click', function () {
            total = parseInt($('#total').val());
            actual = 0;
            correlativo = parseInt($('#inicio').val());
            intervaloSegundos = parseInt($('#intervalo').val());
            ejecucionActiva = true;

            $('#btnAuto').prop('disabled', true);
            $('#btnStop').prop('disabled', false);
            $('#resultados').prepend('<div class="alert alert-info">🚀 Iniciando ejecución automática...</div>');

            ejecutarPruebaIndividual(); // ejecuta la primera
            intervaloID = setInterval(ejecutarPruebaIndividual, intervaloSegundos * 1000);
        });

        $('#btnStop').on('click', function () {
            detenerAutoEjecucion();
            $('#resultados').prepend('<div class="alert alert-warning">⏹ Auto ejecución detenida por el usuario.</div>');
        });




    });
</script>

</body>
</html>
