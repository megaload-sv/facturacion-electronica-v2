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

                if (json.data.length > 0) {

                    numPages = json.num_pages;

                    var tableBody = $('#facturasToMH');
                    tableBody.empty();

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
                        trTable += values.tipoDoc;
                        trTable += '</td>';

                        trTable += '<td>';
                        trTable += values.internal_number;
                        trTable += '</td>';

                        trTable += '<td>';
                        trTable += values.company;
                        trTable += '</td>';

                        trTable += '<td>';
                        trTable += values.code_to_mh;
                        trTable += '</td>';

                        trTable += '<td>';
                        trTable += values.date;
                        trTable += '</td>';

                        trTable += '<td>';
                        trTable += '<a href="#" class="btn btn-sm btn-outline-primary enviarToMH" data-codemh="' + values.code_to_mh + '"> Enviar </a>';
                        trTable += '</td>';
                        /*
                                            var btnString = '';

                                            if(values.status_envio_mh == "1"){
                                                btnString ='<a href="#" class="btn btn-sm btn-outline-success CosultarFactToMH" data-codemh="' + values.code_to_mh + '"> Consultar factura </a>';
                                            }else if (values.status_envio_mh == "2"){
                                                btnString = '<a href="#" class="btn btn-sm btn-outline-danger ReenviarToMH" data-codemh="' + values.code_to_mh + '"> Reenviar Factura </a>'
                                            }

                                            trTable += '<td>';
                                            trTable +=  btnString;
                                            trTable += '</td>';*/

                        trTable += '</tr>';

                    });

                    tableBody.append(trTable);

                    // Actualizar estado de paginación
                    $('#pagination').html(`Página ${json.currentPage} de ${json.num_pages}`);
                    currentPage = parseInt(json.currentPage);
                    $('#prevPage').prop('disabled', currentPage <= 1);

                    $('#nextPage').prop('disabled', ((currentPage > json.num_pages)));


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
            llenarFacturas(currentPage + 1);
        });

    }

    $(document).on('click', '.enviarToMH', function () {

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
                    url: url_base + 'facturas/procesarDTE/' + codemh,
                    type: 'GET',
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

                            llenarFacturas();

                        } else {

                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: json.message
                            });

                        }
                    },

                    error: function (json) {

                        debugger

                        let errorList = '';
                        let message = 'Ha ocurrido un error inesperado en el sistema.';

                        // Validar si viene mensaje
                        if (json && json.message) {
                            message = json.message;
                        }

                        // Validar si existe detalle y es un arreglo
                        if (json && json.responseJSON && json.responseJSON.detalle.length > 0) {

                            errorList = '<div style="max-height:200px;overflow:auto;margin-top:10px;">';
                            errorList += '<ul style="text-align:left;padding-left:20px;">';

                            json.responseJSON.detalle.forEach(function (err) {
                                errorList += `<li style="margin-bottom:5px;">${err}</li>`;
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
                    <b>${message}</b>
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




