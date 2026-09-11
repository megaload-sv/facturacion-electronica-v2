function securityEscape(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, c => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[c]));
}
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
            const nombreReceptor = sello.company || sello.Empresa || 'Sin nombre de receptor';
            const nombreReceptorHtml = $('<div>').text(nombreReceptor).html();

            var btnMensaje = (sello.idsellosDTE == 2 || sello.idsellosDTE == null) ?
                '<button type="button" class="btn btn-danger mensajeMH" data-target="#modalMensaje" data-codigo-generacion="' + securityEscape(sello.codigoGeneracion) + '">Ver Mensaje</button>'
                : '';

            var row = '<tr>' +
                '<td>' + (index + 1) + '</td>' +
                '<td>' + securityEscape(sello.TipoDTE) + '</td>' +
                '<td><span style="font-weight: bold">ERP:</span> ' + securityEscape(sello.identicadorNumInterno) + '<br><span style="font-weight: bold">Codigo de Generacion:</span> ' + securityEscape(sello.codigoGeneracion) + '</td>' +
                '<td>' + securityEscape(sello.numeroControlMH) + '</td>' +
                '<td>' + nombreReceptorHtml + '</td>' +
                '<td>' + securityEscape(sello.fechaFactura) + '</td>' +
                '<td>' + btnMensaje + '</td>' +
                '<td>' +
                '<button class="btn btn-sm btn-outline-primary enviarToMH" data-codemh="' + securityEscape(sello.codigoGeneracion) + '">Volver a procesar</button><br>' +
                '<a target="_blank" href="facturas/descargar-json/' + securityEscape(sello.codigoGeneracion) + '" class="btn btn-sm btn-outline-primary">Descargar JSON</a>' +
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
                type: 'POST',
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

                $('#estadoMensaje').text(response.estado);
                $('#observacionMensaje').text(response.observaciones);
                $('#descripcionMensaje').text(response.descripcionMsg);

                var jsonFormatted = JSON.stringify(response, null, 4);
                $('#mensajeReal').text(jsonFormatted);

            }
        });

        $('#modalMensaje').modal('show');

    });

});

