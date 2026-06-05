$(document).ready(function () {

    $.ajax({
        url: '/facturas/sellos', // nuevo endpoint sin paginar
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            inicializarDataTableProcesadas(json.data);
        }
    });

    function inicializarDataTableProcesadas(data) {
        var tableBody = $('#tablaSellos tbody');
        tableBody.empty();

        $.each(data, function (index, sello) {
            var btnMensaje = (sello.idsellosDTE == 2 || sello.idsellosDTE == null) ?
                '<button type="button" class="btn btn-danger mensajeMH" data-target="#modalMensaje" data-codigo-generacion="' + sello.codigoGeneracion + '">Ver Mensaje</button>'
                : '';

            var row = '<tr>' +
                '<td>' + (index + 1) + '</td>' +
                '<td>' + sello.TipoDTE + '</td>' +
                '<td><span style="font-weight: bold">ERP:</span> ' + sello.identicadorNumInterno + '<br><span style="font-weight: bold">Codigo de Generacion:</span> ' + sello.codigoGeneracion + '</td>' +
                '<td>' + sello.numeroControlMH + '</td>' +
                '<td>' + sello.Empresa + '</td>' +
                '<td>' + sello.fechaFactura + '</td>' +
                '<td>' + btnMensaje + '</td>' +
                '<td>' +
                '<button class="btn btn-sm btn-outline-primary enviarToMH" data-codemh="' + sello.codigoGeneracion + '">Volver a procesar</button><br>' +
                '<a target="_blank" href="facturas/descargar-json/' + sello.codigoGeneracion + '" class="btn btn-sm btn-outline-primary">Descargar JSON</a>' +
                '</td>' +
                '</tr>';

            tableBody.append(row);
        });

        let table = $('#tablaSellos').DataTable({
            order: [[5, 'desc']],
            destroy: true,
            pageLength: 10,
            dom: 'Bfrtip',
            language: {
                url: "/plugins/datatables/i18n/es-ES.json"
            }
        });
    }

    $(document).on('click','.enviarToMH', function () {

        if (confirm("¿Estás seguro que deseas enviar a sellar y firmar?")) {
            $.ajax({
                url: url_base + 'facturas/reenviar-dte/' + $(this).data('codemh'),
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    debugger
                    if (json.error == false) {
                        alert(json.message);
                        llenarFacturas();
                    } else {
                        alert(json.message);
                    }
                }
            });
        }
    });

    $(document).on('click', '.mensajeMH', function (e) {
        e.preventDefault();

        var codigoGeneracion = $(this).data('codigo-generacion');


        $.ajax({
            url: 'facturas/mostrarErrorMH/' + codigoGeneracion,
            type: 'GET',
            success: function (response) {

                $('#estadoMensaje').html(response.estado);
                $('#observacionMensaje').html(response.observaciones);
                $('#descripcionMensaje').html(response.descripcionMsg);

                var jsonFormatted = JSON.stringify(response, null, 4);
                $('#mensajeReal').html(jsonFormatted);

            }
        });

        $('#modalMensaje').modal('show');

    });

});

