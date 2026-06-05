$(document).ready(function () {

    $.ajax({
        url: '/facturas/sellos-archivo',
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            inicializarDataTable(json.data);
        }
    });

    let tablaSellos = null;
    let sellosData = [];

    function inicializarDataTable(data) {

        sellosData = data || [];

        cargarFiltrosDinamicos(sellosData);

        let tableBody = $('#tablaSellos tbody');
        tableBody.empty();

        $.each(sellosData, function (index, sello) {

            let btnMensaje = `
            <button type="button" 
                    class="btn btn-success btn-sm mensajeMH" 
                    data-target="#modalMensaje" 
                    data-codigo-generacion="${sello.codigoGeneracion}">
                Ver Mensaje
            </button>
        `;

            let estadoCorreo = '';

            if (parseInt(sello.correoEnviado) === 1) {
                estadoCorreo = `
                <span class="badge badge-success" title="Correo enviado correctamente">
                    <i class="fas fa-check-circle"></i> Enviado
                </span>
            `;
            } else {
                let mensajeErrorCorreo = sello.errorCorreo ? sello.errorCorreo : 'Correo no enviado';

                estadoCorreo = `
                <span class="badge badge-danger" title="${mensajeErrorCorreo}">
                    <i class="fas fa-times-circle"></i> No enviado
                </span>
            `;
            }

            let rowClass = parseInt(sello.idEstadoDTE) === 4 ? 'fila-inactiva' : '';

            let btnInvalidar = '';

            if (parseInt(sello.idEstadoDTE) !== 4) {
                btnInvalidar = `
                <a href="facturas/invalidar-json/${sello.codigoGeneracion}" 
                   class="btn btn-sm btn-outline-primary invalidarDTE" 
                   data-codigo-generacion="${sello.codigoGeneracion}">
                    Invalidar DTE
                </a>
            `;
            }

            let row = `
            <tr class="${rowClass}" 
                data-tipo-dte="${sello.TipoDTE}" 
                data-estado-dte="${sello.estadoNombre}" 
                data-fecha-factura="${sello.fechaFactura}">
                
                <td>${index + 1}</td>
                <td>${sello.TipoDTE}</td>
                <td>
                    <span style="font-weight: bold">ERP:</span> 
                    <em>CRM id</em> ${sello.identicadorNumInterno}, 
                    <em>CRM #</em> ${sello.numeroCRM}
                    <br>
                    <span style="font-weight: bold">Código de Generación:</span> 
                    ${sello.codigoGeneracion}
                </td>
                <td>${sello.numeroControlMH}</td>
                <td>${sello.Empresa}</td>
                <td>${sello.fechaFactura}</td>
                <td>${sello.estadoNombre}</td>
                <td>${btnMensaje}</td>
                <td>${estadoCorreo}</td>
                <td>
                    <a target="_blank" href="facturas/generar-pdf/${sello.codigoGeneracion}" class="btn btn-sm btn-outline-primary">Generar PDF</a><br>
                    <a target="_blank" href="facturas/descargar-json/${sello.codigoGeneracion}" class="btn btn-sm btn-outline-primary">Descargar JSON</a><br>
                    <a href="facturas/enviar-correo/${sello.codigoGeneracion}" class="btn btn-sm btn-outline-primary enviarCorreo" data-codigo-generacion="${sello.codigoGeneracion}">Enviar Correo</a><br>
                    ${btnInvalidar}
                </td>
            </tr>
        `;

            tableBody.append(row);
        });

        if ($.fn.DataTable.isDataTable('#tablaSellos')) {
            $('#tablaSellos').DataTable().clear().destroy();
        }

        tablaSellos = $('#tablaSellos').DataTable({
            order: [[5, 'desc']],
            pageLength: 10,
            dom: 'Bfrtip',
            language: {
                url: "/plugins/datatables/i18n/es-ES.json"
            }
        });

        aplicarEventosFiltros();
    }

    function cargarFiltrosDinamicos(data) {

        let tipos = [...new Set(data.map(item => item.TipoDTE).filter(Boolean))];
        let estados = [...new Set(data.map(item => item.estadoNombre).filter(Boolean))];

        let filtroTipo = $('#filtroTipoDTE');
        let filtroEstado = $('#filtroEstadoDTE');

        filtroTipo.empty().append('<option value="">Todos</option>');
        filtroEstado.empty().append('<option value="">Todos</option>');

        tipos.sort().forEach(function (tipo) {
            filtroTipo.append(`<option value="${tipo}">${tipo}</option>`);
        });

        estados.sort().forEach(function (estado) {
            filtroEstado.append(`<option value="${estado}">${estado}</option>`);
        });
    }

    function aplicarEventosFiltros() {

        $('#filtroTipoDTE, #filtroEstadoDTE, #filtroFechaDesde, #filtroFechaHasta').off('change').on('change', function () {
            tablaSellos.draw();
        });

        $('#btnLimpiarFiltros').off('click').on('click', function () {
            $('#filtroTipoDTE').val('');
            $('#filtroEstadoDTE').val('');
            $('#filtroFechaDesde').val('');
            $('#filtroFechaHasta').val('');

            tablaSellos.search('').columns().search('');
            tablaSellos.draw();
        });
    }

    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {

        if (settings.nTable.id !== 'tablaSellos') {
            return true;
        }

        let tipoFiltro = $('#filtroTipoDTE').val();
        let estadoFiltro = $('#filtroEstadoDTE').val();
        let fechaDesde = $('#filtroFechaDesde').val();
        let fechaHasta = $('#filtroFechaHasta').val();

        let tipoTabla = data[1];
        let fechaTabla = data[5];
        let estadoTabla = data[6];

        if (tipoFiltro && tipoTabla !== tipoFiltro) {
            return false;
        }

        if (estadoFiltro && estadoTabla !== estadoFiltro) {
            return false;
        }

        if (fechaDesde || fechaHasta) {
            let fechaSoloDia = fechaTabla.substring(0, 10);

            if (fechaDesde && fechaSoloDia < fechaDesde) {
                return false;
            }

            if (fechaHasta && fechaSoloDia > fechaHasta) {
                return false;
            }
        }

        return true;
    });

    $(document).on('click', '.descargarJSON', function () {

        var codigoGeneracion = $(this).data('codigo-generacion');

        $.ajax({
            url: 'facturas/descargar-json/' + codigoGeneracion,
            method: 'GET',
            xhrFields: {
                responseType: 'blob'
            },
            success: function (data, status, xhr) {
                debugger
                var filename = codigoGeneracion + ".json";
                var blob = new Blob([data], {type: "application/json"});

                // Crear un enlace temporal para la descarga
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = filename;

                // Click en el enlace para iniciar la descarga
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            },
            error: function (xhr, status, error) {
                alert('Error al descargar el archivo JSON.');
            }
        });
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

    $(document).on('click', '.invalidarDTE', function (e) {
        e.preventDefault();

        if ($(this).is('[disabled]')) {
            e.preventDefault();
            return false;
        }

        $('#mhc_invalidation_type_id').val('');

        $('#issuer_name').val('');
        $('#issuer_document_type').val('');
        $('#issuer_document_number').val('');
        $('#receiver_name').val('');
        $('#mhc_receiver_document_type_id').val('');
        $('#receiver_document_number').val('');

        var codigoGeneracion = $(this).data('codigo-generacion');

        $('#dteUUID').val(codigoGeneracion);

        $('#code_generation_r').val('').empty().append('<option value="">Seleccione un DTE</option>');

        $('#modalInvalidarDTE').modal('show');
        $.ajax({
            url: '/dte-invalidar/' + codigoGeneracion,
            type: 'GET',
            success: function (response) {

                $.each(response.listDTE, function (index, item) {
                    $('#code_generation_r').append(
                        $('<option>', {
                            value: item.codigoGeneracion,
                            text: item.codigoGeneracion
                        })
                    );

                });


                $('#dteUUID').val(response.infoDTE[0].codigoGeneracion);
                $('#mhCode').val(response.infoDTE[0].codigoTipoDTE);
                $('#transmission_datetime').val(response.infoDTE[0].fechaProcesamiento);
                $('#issuer_name').val();
                $('#issuer_document_type').val('');
                $('#issuer_document_number').val('');
                $('#receiver_name').val(response.infoDTE[0].nombreReceptor);
                $('#mhc_receiver_document_type_id').val(response.infoDTE[0].tipoDocumentoReceptor);
                $('#receiver_document_number').val(response.infoDTE[0].numDocumentoReceptor);


            }
        });


    });

    $(document).on('click', '.processInvalidarDTE', function (e) {
        e.preventDefault();

        var form = $('#formInvalidDTE');

        form.validate({
            rules: {
                mhc_invalidation_type_id: {
                    required: true
                },
                issuer_name: {
                    required: true,
                    minlength: 5,
                    maxlength: 100
                },
                issuer_document_type: {
                    required: true
                },
                issuer_document_number: {
                    required: true,
                    minlength: 3,
                    maxlength: 20
                },
                receiver_name: {
                    required: true,
                    minlength: 5,
                    maxlength: 100
                },
                mhc_receiver_document_type_id: {
                    required: true
                },
                receiver_document_number: {
                    required: true,
                    minlength: 3,
                    maxlength: 20
                }
            },
            messages: {
                mhc_invalidation_type_id: "Seleccione un motivo de invalidación.",
                issuer_name: {
                    required: "Ingrese el nombre del responsable de la invalidación.",
                    minlength: "Debe tener al menos 5 caracteres.",
                    maxlength: "No puede exceder los 100 caracteres."
                },
                issuer_document_type: "Seleccione un tipo de documento.",
                issuer_document_number: {
                    required: "Ingrese el número de documento del responsable.",
                    minlength: "Debe tener al menos 3 caracteres.",
                    maxlength: "No puede exceder los 20 caracteres."
                },
                receiver_name: {
                    required: "Ingrese el nombre del solicitante.",
                    minlength: "Debe tener al menos 5 caracteres.",
                    maxlength: "No puede exceder los 100 caracteres."
                },
                mhc_receiver_document_type_id: "Seleccione un tipo de documento del solicitante.",
                receiver_document_number: {
                    required: "Ingrese el número de documento del solicitante.",
                    minlength: "Debe tener al menos 3 caracteres.",
                    maxlength: "No puede exceder los 20 caracteres."
                }
            },
            errorClass: "is-invalid",
            validClass: "is-valid",
            highlight: function (element) {
                $(element).addClass("is-invalid").removeClass("is-valid");
            },
            unhighlight: function (element) {
                $(element).removeClass("is-invalid").addClass("is-valid");
            },
            errorPlacement: function (error, element) {
                error.addClass("invalid-feedback");
                error.insertAfter(element);
            }
        });

        if (!form.valid()) {
            Swal.fire({
                icon: 'warning',
                title: 'Formulario incompleto',
                text: 'Por favor complete correctamente los campos requeridos antes de continuar.',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#1a1bb3'
            });
            return;
        }

        Swal.fire({
            title: '¿Invalidar DTE?',
            text: 'Se enviará la solicitud de invalidación. Esta acción debe realizarse solo si los datos han sido revisados.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1a1bb3',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {

            if (!result.isConfirmed) {
                return;
            }

            Swal.fire({
                title: 'Procesando...',
                html: 'Enviando invalidación del DTE, por favor espere.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '/dte-invalidar/',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',

                success: function (response) {

                    if (response && response.success === true) {

                        Swal.fire({
                            icon: 'success',
                            title: 'DTE invalidado',
                            text: response.message || 'El DTE se ha invalidado correctamente.',
                            confirmButtonColor: '#1a1bb3'
                        }).then(() => {
                            $('#modalInvalidarDTE').modal('hide');
                            form[0].reset();
                            form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');

                            // refrescar tabla
                            location.reload();
                            // o llamar la función que llena la tabla nuevamente
                        });

                    } else {

                        let detalleHtml = '';

                        if (response && Array.isArray(response.detalle) && response.detalle.length > 0) {
                            detalleHtml = '<div style="max-height:200px;overflow:auto;margin-top:10px;">';
                            detalleHtml += '<ul style="text-align:left;padding-left:20px;">';

                            response.detalle.forEach(function (item) {
                                detalleHtml += `<li style="margin-bottom:5px;">${item}</li>`;
                            });

                            detalleHtml += '</ul></div>';
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'No fue posible invalidar el DTE',
                            html: `
                                    <div style="font-size:14px;text-align:left;">
                                        <div style="margin-bottom:10px;">
                                            <b>${response && response.message ? response.message : 'Ha ocurrido un problema inesperado en el proceso.'}</b>
                                        </div>
                                        ${detalleHtml || '<div>No se recibieron detalles adicionales del error.</div>'}
                                    </div>
                                `,
                            width: 600,
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#d33'
                        });
                    }
                },

                error: function (xhr) {

                    let message = 'Ha ocurrido un error del servidor al intentar invalidar el DTE.';
                    let detalleHtml = '';

                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }

                        if (Array.isArray(xhr.responseJSON.detalle) && xhr.responseJSON.detalle.length > 0) {
                            detalleHtml = '<div style="max-height:200px;overflow:auto;margin-top:10px;">';
                            detalleHtml += '<ul style="text-align:left;padding-left:20px;">';

                            xhr.responseJSON.detalle.forEach(function (item) {
                                detalleHtml += `<li style="margin-bottom:5px;">${item}</li>`;
                            });

                            detalleHtml += '</ul></div>';
                        }
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error del servidor',
                        html: `
                        <div style="font-size:14px;text-align:left;">
                            <div style="margin-bottom:10px;">
                                <b>${message}</b>
                            </div>
                            ${detalleHtml || '<div>No se pudo obtener mayor detalle del error.</div>'}
                        </div>
                    `,
                        width: 600,
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#d33'
                    });
                }
            });
        });


    });

    $(document).on('click', '.enviarCorreo',function (e){
        e.preventDefault();

        var codigoGeneracion = $(this).data('codigo-generacion');

        $.ajax({
            url: '/facturas/dte-correo-data/' + codigoGeneracion,
            type: 'GET',
            success: function (response) {

                $('#reenvioCorreo').find('#dteUUID').val(codigoGeneracion);
                $('#correoReenvio').val(response.correoReceptor);


            },
            error: function () {

            },
            complete: function () {
                $('#modalReenvioCorreo').modal('show');
            }
        });



    });

    $(document).on('click', '.reenviarCorreoFrm', function(e){
        e.preventDefault();

        var codigoGeneracion = $('#reenvioCorreo').find('#dteUUID').val();

        $.ajax({
            url: '/facturas/dte-correo-data-process/' + codigoGeneracion,
            type: 'POST',
            dataType: 'json',
            success: function (response) {

                if (response.error === false) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Correo reenviado',
                        text: response.message || 'El correo fue reenviado correctamente.',
                        confirmButtonColor: '#1a1bb3'
                    }).then(() => {
                        $('#modalReenvioCorreo').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo reenviar',
                        text: response.message || 'No fue posible reenviar el correo.',
                        confirmButtonColor: '#d33'
                    });
                }
            },
            error: function (xhr) {
                let message = 'No se pudo reenviar el correo.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message,
                    confirmButtonColor: '#d33'
                });
            }
        });
    });

    $(document).on('change', '#mhc_invalidation_type_id', function (e) {
        e.preventDefault();
        validateDteInvalidation();
    });
    function validateDteInvalidation() {


        var code = $('#mhCode').val();
        var codeVal = "";
        if (code === "01") {
            codeVal = "FE";
        } else if (code === "11") {
            codeVal = "FEXE";
        } else if (code === "14") {
            codeVal = "FSEE";
        } else if (code === "03") {
            codeVal = "CCF";
        } else if (code === "04") {
            codeVal = "NRE";
        }


        var transmissionDatetimeStr = $('#transmission_datetime').val();
        var internalCode = codeVal;// Tipo de DTE (FE, FEXE, NRE, FSEE)
        var invalidationTypeId = $('select[name="mhc_invalidation_type_id"]').val();

        if (!transmissionDatetimeStr || !internalCode || !invalidationTypeId) {
            return;
        }

        var transmissionDate = new Date(transmissionDatetimeStr);
        var currentDate = new Date();
        var limitDate = new Date(transmissionDate);

        //invalidación por tipo de documento y motivo
        // siempre ando pensando en hacer una estrucura de datos que rerrorer
        // pracicamente esta es la tabla de reglas de ministerio de hacienda
        var rules = {
            "FE": { // Factura Electrónica (FE)
                "1": {days: 1, isDayLimited: true},
                "2": {days: 90, isDayLimited: false},
                "3": {days: 90, isDayLimited: false}
            },
            "FEXE": { // Factura de Exportación Electrónica (FEXE)
                "1": {days: 1, isDayLimited: true},
                "2": {days: 90, isDayLimited: false},
                "3": {days: 90, isDayLimited: false}
            },
            "NRE": { // Nota de Remisión Electrónica (NRE)
                "1": {days: 1, isDayLimited: true},
                "2": {days: 1, isDayLimited: true},
                "3": {days: 1, isDayLimited: true}
            },
            "FSEE": { // Factura de Sujeto Excluido Electrónica (FSEE)
                "1": {days: 1, isDayLimited: true},
                "2": {days: 1, isDayLimited: true},
                "3": {days: 1, isDayLimited: true}
            },
            "CCF": { // Comprobante Credito Fiscal (CCF)
                "1": {days: 1, isDayLimited: true},
                "2": {days: 1, isDayLimited: true},
                "3": {days: 1, isDayLimited: true}
            }
        };

        var rule = rules[internalCode][invalidationTypeId];

        if (rule.isDayLimited) {
            limitDate.setDate(limitDate.getDate() + rule.days); // sumo lo dias para comparar mas abajo
            limitDate.setHours(23, 59, 59); // esto es para ajuste la fecha y cumplir con la regla
        } else {
            limitDate.setDate(limitDate.getDate() + rule.days);
        }

        if (currentDate > limitDate) {
            alert("Invalidación no permitida\nNo es posible invalidar este DTE. Ha excedido el tiempo permitido.");
            $('.processInvalidDTEModal').prop('disabled', true);

        } else {
            // Habilitar el botón de invalidación
            $('.processInvalidDTEModal').prop('disabled', false);
        }
    }


});
