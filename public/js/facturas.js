function securityEscape(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, c => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[c]));
}
$(document).ready(function(){

    let currentPage = 1;
    let numPages = 0;
    const limit = 10;


    llenarFacturas(currentPage);

    //setInterval(llenarFacturas, 15000);

    function llenarFacturas(page = 1) {

        $(document).ajaxStop($.unblockUI);

        $.ajax({
            url: url_api+'/'+page+'/'+limit,
            type: 'GET',
            data: { page: page, limit: limit },
            dataType: 'json',
            beforeSend: function () {
                $.blockUI({
                    message: `
                <div style="padding:15px;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <h5>Cargando facturas...</h5>
                </div>
            `,
                    css: {
                        border: 'none',
                        padding: '15px',
                        backgroundColor: '#000',
                        borderRadius: '10px',
                        opacity: .7,
                        color: '#fff'
                    }
                });
            },
            success: function (json) {

                numPages = Math.max(0, parseInt(json.num_pages, 10) || 0);
                currentPage = Math.max(1, parseInt(json.currentPage, 10) || page);

                var tableBody = $('#facturasToMH tbody');
                tableBody.empty();

                // Al enviar la última factura de una página, volver a una página válida.
                if (json.data.length === 0 && currentPage > 1) {
                    llenarFacturas(Math.max(1, Math.min(currentPage - 1, numPages)));
                    return;
                }

                $('#pagination').text(numPages > 0 ? `Página ${currentPage} de ${numPages}` : 'Sin facturas pendientes');
                $('#prevPage').prop('disabled', currentPage <= 1);
                $('#nextPage').prop('disabled', currentPage >= numPages);

                if (json.data.length > 0) {

                    var trTable = '';

                    console.log(json.data);

                    $.each(json.data, function (index, values) {

                        trTable += "<tr>";

                        trTable += "<td>";
                        trTable += '<div>';
                        trTable += ' ' + (index + 1) + ' ';
                        trTable += '</div>';
                        trTable += '</td>';

                        trTable += '<td>';
                        trTable += securityEscape(values.tipoDoc);
                        trTable += '</td>';

                        trTable += '<td>';
                        trTable += securityEscape(values.internal_number);
                        trTable += '</td>';

                        trTable += '<td>';
                        trTable += securityEscape(values.company);
                        trTable += '</td>';

                        trTable += '<td>';
                        trTable += securityEscape(values.code_to_mh);
                        trTable += '</td>';

                        trTable += '<td>';
                        trTable += securityEscape(values.date);
                        trTable += '</td>';

                        trTable += '<td>';
                        trTable += '<a href="#" class="btn btn-sm btn-outline-primary enviarToMH" data-codemh="' + securityEscape(values.code_to_mh) + '" data-tipo-doc="' + securityEscape(values.prefix) + '"> Enviar </a>';
                        trTable += '</td>';
                        /*
                                            var btnString = '';

                                            if(values.status_envio_mh == "1"){
                                                btnString ='<a href="#" class="btn btn-sm btn-outline-success CosultarFactToMH" data-codemh="' + securityEscape(values.code_to_mh) + '"> Consultar factura </a>';
                                            }else if (values.status_envio_mh == "2"){
                                                btnString = '<a href="#" class="btn btn-sm btn-outline-danger ReenviarToMH" data-codemh="' + securityEscape(values.code_to_mh) + '"> Reenviar Factura </a>'
                                            }

                                            trTable += '<td>';
                                            trTable +=  btnString;
                                            trTable += '</td>';*/

                        trTable += '</tr>';

                    });

                    tableBody.append(trTable);

                } else {
                    tableBody.append('<tr><td colspan="7" class="text-center">No hay facturas pendientes de enviar.</td></tr>');
                }

            },
            error: function () {
                Swal.fire('Error', 'Ocurrió un problema al cargar las facturas.', 'error');
            },

            complete: function () {
                $.unblockUI();
            }


        });

        // Control de botones de paginación
        $('#prevPage').off('click').click(function(e) {
            e.preventDefault();
            if (currentPage > 1) llenarFacturas(currentPage - 1);
        });
        $('#nextPage').off('click').click(function(e) {
            e.preventDefault();
            if (currentPage < numPages) llenarFacturas(currentPage + 1);
        });

    }

    $(document).on('click', '.enviarToMH', function (event) {
        event.preventDefault();

        let codemh = $(this).data('codemh');
        let button = $(this);

        Swal.fire({
            title: '¿Enviar a MH?',
            text: 'Se procederá a firmar y enviar el DTE al Ministerio de Hacienda.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1a1bb3',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, enviar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {

            if (result.isConfirmed) {

                // Loader elegante
                Swal.fire({
                    title: 'Procesando...',
                    html: 'Firmando y enviando DTE a MH',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: url_base + 'facturas/procesarDTE/' + codemh + '/' + button.data('tipo-doc'),
                    type: 'POST',
                    dataType: 'json',

                    success: function (json) {

                        if (json.error === false) {

                            Swal.fire({
                                icon: 'success',
                                title: 'DTE Enviado',
                                text: json.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            llenarFacturas(currentPage);

                        } else {

                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: json.message
                            });

                        }
                    },

                    error: function (json) {

                        let errorList = '';
                        let message = 'Ha ocurrido un error inesperado en el sistema.';
                        const response = json && json.responseJSON;
                        const escapeHtml = (value) => $('<div>').text(String(value)).html();

                        // Validar si viene mensaje
                        if (response && response.message) {
                            message = response.message;
                        }

                        // Validar si existe detalle y es un arreglo
                        if (response && Array.isArray(response.detalle) && response.detalle.length > 0) {

                            errorList = '<div style="max-height:200px;overflow:auto;margin-top:10px;">';
                            errorList += '<ul style="text-align:left;padding-left:20px;">';

                            response.detalle.forEach(function (err) {
                                errorList += `<li style="margin-bottom:5px;">${escapeHtml(err)}</li>`;
                            });

                            errorList += '</ul></div>';

                        } else {

                            // Mensaje fallback si detalle no existe
                            errorList = `
            <div style="margin-top:10px;color:#6c757d;">
                No se recibieron detalles del error. 
                Si el problema persiste, contacte al administrador del sistema.
            </div>
        `;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error en DTE ' + codemh,
                            html: `
            <div style="font-size:14px">
                <div style="margin-bottom:10px">
                    <b>${escapeHtml(message)}</b>
                </div>
                ${errorList}
            </div>
        `,
                            width: 600,
                            confirmButtonText: 'Entendido'
                        });


                    }
                });
            }

        });

    });
});




