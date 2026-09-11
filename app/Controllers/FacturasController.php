<?php

namespace App\Controllers;

use App\Models\CatTipoInvalidacionModel;
use App\Models\CorrelativosFacturasModel;
use App\Models\FacturaModel;
use App\Models\MantenimientosModel;
use App\Models\MenuModel;
use App\Models\SeguridadModel;
use App\Models\SellosdteModel;
use App\Models\CatTipoIdentificacionReceptorModel;
use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\I18n\Time;
use App\Libraries\JsonValidator;
use App\Services\EmailService;
use PHPUnit\Exception;
use stdClass;
use TCPDF;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use function PHPUnit\Framework\fileExists;

class FacturasController extends BaseController
{

    private $modelSeguridad;
    private $modelFactura;
    private $codigoPuntoVenta;
    private $correlativoFacturas;
    private $modelMantenimientos;


    public function __construct()
    {
        //parent::__construct();
        $this->modelSeguridad = new SeguridadModel();
        $this->modelFactura = new FacturaModel();
        $this->correlativoFacturas = new CorrelativosFacturasModel();
        $this->modelMantenimientos = new MantenimientosModel();
    }

    public function index()
    {

        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        $dataSeguridad = $this->modelSeguridad->getConfigByEnvironment();

        return $this->render('pages/facturas', ['url_api' => base_url('facturas/pendientes')]);
    }

    public function pendientes(int $page = 1, int $limit = 10)
    {
        $page = max(1, min($page, 100000));
        $limit = max(1, min($limit, 100));
        $config = $this->modelSeguridad->getConfigByEnvironment();
        $url = rtrim($config['urlApiFacturas'] ?? '', '/');
        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return $this->response->setStatusCode(503)->setJSON(['error' => 'Configure HTTPS en la API de facturas.']);
        }
        try {
            $upstream = service('curlrequest')->get($url . '/' . $page . '/' . $limit, [
                'verify' => true, 'timeout' => 20, 'connect_timeout' => 5, 'allow_redirects' => false,
            ]);
            $data = json_decode($upstream->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return $this->response->setJSON($data);
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo consultar la API de facturas.');
            return $this->response->setStatusCode(502)->setJSON(['error' => 'No se pudo consultar el ERP.']);
        }
    }

    /**
     * @param mixed $codigoPuntoVenta
     */
    public function setCodigoPuntoVenta($codigoPuntoVenta): void
    {
        $this->codigoPuntoVenta = $codigoPuntoVenta;
    }

    public function procesarDTE(string $factura, string $tipoDoc = null)
    {

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $client = \Config\Services::curlrequest();
            $modelSellos = new SellosdteModel();

            $idSelloInterno = 0;
            $dataSelloProcesado = array();

            $DTE = $modelSellos->getDTEByCodigoGeneracion($factura);
            $countRegisters = ($DTE == null ? 0 : count($DTE));

            var_dump($DTE);
            exit;

            if ($DTE == null || $countRegisters == 0) {
                $dataSeguridad = $this->modelSeguridad->getConfigByEnvironment();

                //Obteniendo el dteJson
                $responseFactura = $client->request('GET', $dataSeguridad['urlGetJson'] . $factura . '/' . $tipoDoc, ['verify' => true, 'http_errors' => false]);

                $newData = json_decode($responseFactura->getBody());

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $db->transRollback();
                    return $this->response->setStatusCode(400)->setJSON([
                        'error' => true,
                        'message' => 'La respuesta del ERP no contiene un JSON válido.',
                        'detalle' => [json_last_error_msg()],
                    ]);
                }

                $validator = new JsonValidator();
                $validacion = $validator->validateErpResponse($newData);

                if ($validacion['error']) {
                    $db->transRollback();
                    return $this->response->setStatusCode(400)->setJSON($validacion);
                }

                $this->setCodigoPuntoVenta($newData->datainfo->codigoPuntoVenta);
                $tipoDte = $newData->data->identificacion->tipoDte;
                $newData->datainfo->codigoTipoDTE = $tipoDte;
                $nodoReceptor = $validator->getRecipientNode($tipoDte);
                $receptor = $newData->data->$nodoReceptor ?? null;

                //Obteniendo informacion general para incorporar al json
                $factDatosGenerales = $this->modelFactura->get_informacion_general($this->codigoPuntoVenta);
                if (!$factDatosGenerales) {
                    throw new \RuntimeException('No existe configuración del emisor para el punto de venta.');
                }

                // Solo transformar direcciones presentes. Los campos ausentes,
                // tipos incorrectos y nodos null los decide el esquema del DTE.
                $direccionReceptor = $receptor instanceof \stdClass ? ($receptor->direccion ?? null) : null;
                if ($tipoDte !== '11' && $newData->datainfo->codPais === 'SV'
                    && $direccionReceptor instanceof \stdClass
                    && is_string($direccionReceptor->municipio ?? null)
                    && is_string($direccionReceptor->departamento ?? null)
                    && $direccionReceptor->municipio !== '' && $direccionReceptor->departamento !== '') {
                    $distritoReceptor = $this->modelFactura->getDistritoByCodeMunicipioCodeDepartamento($direccionReceptor->municipio, $direccionReceptor->departamento);
                    if (!$distritoReceptor || !isset($distritoReceptor->codigo)) {
                        $db->transRollback();
                        return $this->response->setStatusCode(400)->setJSON([
                            'error' => true,
                            'message' => 'Revisa la dirección del destinatario.',
                            'detalle' => ["[data.{$nodoReceptor}.direccion] No se encontró el distrito para el municipio y departamento indicados."],
                        ]);
                    }
                    $direccionReceptor->municipio = $distritoReceptor->codigo;
                }

                $anioActual = (int)date('Y');

                //$numeroEstimacion = ($newData->datainfo->numeroEstimacion == "") ? $newData->datainfo->numeroEstimacion : null;
                $factCorrelativo = $this->modelFactura->get_correlativo_por_tipo_documento($newData->datainfo->codigoTipoDTE, $anioActual);

                if (empty($factCorrelativo) === true) {
                    $this->correlativoFacturas->setNextCorrelativo($tipoDte, $factDatosGenerales->codigoPuntoVenta, $anioActual);
                    $factCorrelativo = $this->modelFactura->get_correlativo_por_tipo_documento($newData->datainfo->codigoTipoDTE, $anioActual);
                }

                $correlativo = $factCorrelativo->corre;
                $arreglo = explode('-', $correlativo);

                $idEnvio = intval($arreglo[3]);

                //seteando valores generales en el json, terminando de construir
                $newData->data->identificacion->ambiente = $dataSeguridad['ambiente'];
                $newData->data->identificacion->numeroControl = $factCorrelativo->corre;
                $newData->data->emisor ??= new \stdClass();
                $newData->data->emisor->direccion ??= new \stdClass();
                $newData->data->emisor->direccion->departamento = $factDatosGenerales->codigoDepartamento;
                $newData->data->emisor->direccion->municipio = $factDatosGenerales->codigoDistrito;
                $newData->data->emisor->direccion->complemento = $factDatosGenerales->direccion;
                $validator->completeEmisor($tipoDte, $newData->data->emisor, [
                    'nit' => str_replace('-', '', $factDatosGenerales->nit),
                    'nrc' => $factDatosGenerales->nrc,
                    'nombre' => $factDatosGenerales->nombreRazonSocial,
                    'codActividad' => $factDatosGenerales->codigoActividad,
                    'descActividad' => $factDatosGenerales->descripcionActividad,
                    'nombreComercial' => $factDatosGenerales->nombreComercial,
                    'tipoEstablecimiento' => $factDatosGenerales->codigoTipoEstablecimiento,
                    'telefono' => $factDatosGenerales->telefono,
                    'correo' => $factDatosGenerales->correo,
                    'codEstableMH' => $factDatosGenerales->codigoMH,
                    'codEstable' => $factDatosGenerales->idSucursal,
                    'codPuntoVentaMH' => $factDatosGenerales->codigoPuntoVentaMH,
                    'codPuntoVenta' => $factDatosGenerales->codigoPuntoVenta,
                ]);

                // Validar el DTE definitivo, antes de agregar firma y sello.
                $result = $validator->validateJson($tipoDte, $newData->data);

                if (!$result['valid']) {
                    $db->transRollback();
                    return $this->response->setStatusCode(400)->setJSON($result);
                }
                unset($client);

                //header('Content-Type: application/json; charset=utf-8');            
                //echo json_encode($newData->data);
                //exit;

                $documentoFirmado = $this->firmarDTE($newData->data, $dataSeguridad);

                var_dump($documentoFirmado);
                exit;

                // Preparando para guardar el sello de forma local, y envio a MH
                $dataSello = [
                    'identicadorNumInterno' => $newData->datainfo->identicadorNumInterno,
                    'codigoGeneracion' => $newData->datainfo->codigoGeneracion,
                    'correlativoFactCRM' => $newData->datainfo->correlativoFactCRM,
                    'numeroControlMH' => $factCorrelativo->corre,
                    'codigoTipoDTE' => $newData->datainfo->codigoTipoDTE,
                    'version' => $newData->datainfo->version,
                    'fechaFactura' => $newData->datainfo->fechaFactura,
                    'firmaFactura' => $documentoFirmado,
                    'fechaFirma' => date("Y/m/d H:i:s"),
                    'usuarioFirma' => session()->get('username'),
                    'codigoPuntoVenta' => $newData->datainfo->codigoPuntoVenta
                ];

                //$idSelloInterno = $modelSellos->insert($dataSello);


                $token = $this->validarToken($dataSeguridad);


                $responseSelloData = $this->sellarDTE($dataSeguridad, $token, $idEnvio, strval($newData->datainfo->codigoTipoDTE), $newData->datainfo->version, $newData->datainfo->codigoGeneracion, $documentoFirmado);


                if ($responseSelloData->estado == "RECHAZADO") {

                    //guardar sello del DTE con error
                    $dataSello['jsonDTE'] = json_encode($newData->data);

                    $fechaOriginal = $responseSelloData->fhProcesamiento ?? null;
                    $fechaFormateada = null;

                    if ($fechaOriginal) {
                        $fechaObj = \DateTime::createFromFormat('d/m/Y H:i:s', $fechaOriginal);
                        if ($fechaObj) {
                            $fechaFormateada = $fechaObj->format('Y-m-d H:i:s');
                        }
                    }

                    $dataSello['usuarioSello'] = session()->get('username');
                    $dataSello['fechaSello'] = $fechaFormateada;
                    $dataSello['errorMH'] = json_encode($responseSelloData);
                    $dataSello['idEstadoDTE'] = 2;

                } else {

                    //Agregando firma y sello al json
                    $newData->data->firmaElectronica = $documentoFirmado;
                    $newData->data->selloRecibido = $responseSelloData->selloRecibido;

                    $dataSello['jsonDTE'] = json_encode($newData->data);

                    $fechaOriginal = $responseSelloData->fhProcesamiento ?? null;
                    $fechaFormateada = null;

                    if ($fechaOriginal) {
                        $fechaObj = \DateTime::createFromFormat('d/m/Y H:i:s', $fechaOriginal);
                        if ($fechaObj) {
                            $fechaFormateada = $fechaObj->format('Y-m-d H:i:s');
                        }
                    }

                    //guardar en sello Dte
                    $dataSello['usuarioSello'] = session()->get('username');
                    $dataSello['fechaSello'] = $fechaFormateada;
                    $dataSello['errorMH'] = json_encode($responseSelloData);
                    $dataSello['idEstadoDTE'] = 1;
                }

                $idSelloInterno = $modelSellos->insert($dataSello);


                $this->correlativoFacturas->setNextCorrelativo($tipoDte, $factDatosGenerales->codigoPuntoVenta, $anioActual);

                $db->transCommit();
                $jsonDTE = json_encode($newData->data);

                $datos = new \stdClass();
                $datos->receptor = new \stdClass();
                $datos->dte = new \stdClass();

                // Receptor
                $datos->receptor->nombre = $receptor->nombre ?? '';
                $datos->receptor->correo = $receptor->correo ?? '';

                // DTE
                $datos->dte->fecEmi = $newData->data->identificacion->fecEmi ?? '';
                $datos->dte->codigoGeneracion = $newData->data->identificacion->codigoGeneracion ?? $newData->datainfo->codigoGeneracion;
                $datos->dte->ambiente = $newData->data->identificacion->ambiente ?? '';

                $dataSelloProcesado['procesadoMH'] = $responseSelloData;
                $dataSelloProcesado['error'] = false;
                $dataSelloProcesado['message'] = 'DTE procesado correctamente en Facturación Electrónica.';

                // 1. Primero actualizar ERP si el DTE fue aceptado
                if (($responseSelloData->estado ?? '') !== 'RECHAZADO') {
                    try {
                        $clientProcesarDocERP = new CURLRequest(
                            new \Config\App(),
                            new \CodeIgniter\HTTP\URI()
                        );

                        $fechaOriginal = $responseSelloData->fhProcesamiento ?? null;
                        $fechaFormateada = null;

                        if ($fechaOriginal) {
                            $fechaObj = \DateTime::createFromFormat('d/m/Y H:i:s', $fechaOriginal);
                            if ($fechaObj) {
                                $fechaFormateada = $fechaObj->format('Y-m-d H:i:s');
                            }
                        }

                        $payloadERP = [
                            'numeroControl'   => $newData->data->identificacion->numeroControl ?? '',
                            'estadoMH'        => $responseSelloData->estado ?? '',
                            'selloRecibido'   => $responseSelloData->selloRecibido ?? '',
                            'fhProcesamiento' => $fechaFormateada ?? '',
                            'jsonDTE'         => $newData->data,
                            'tipoDTE'         => $newData->datainfo->prefix ?? '',
                        ];

                        $facturaProcesada = $clientProcesarDocERP->request(
                            'PUT',
                            $dataSeguridad['urlSetJson'] . '/' . $newData->datainfo->codigoGeneracion,
                            [
                                'verify'      => true,
                                'http_errors' => false,
                                'headers'     => [
                                    'Content-Type' => 'application/json'
                                ],
                                'body'        => json_encode($payloadERP, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                            ]
                        );

                        $respuestaERP = json_decode($facturaProcesada->getBody(), true);

                        $dataSelloProcesado['procesadoERP'] = $respuestaERP;
                        $dataSelloProcesado['erpActualizado'] = true;
                    } catch (\Throwable $e) {
                        log_message('error', 'Error actualizando ERP para DTE ' . $newData->datainfo->codigoGeneracion . ': ' . $e->getMessage());

                        $dataSelloProcesado['erpActualizado'] = false;
                        $dataSelloProcesado['erpError'] = $e->getMessage();
                    }
                }

                // 2. Luego intentar enviar correo, pero sin afectar ERP
                $correo = trim((string)($datos->receptor->correo ?? ''));
                $esProduccion = config(\Config\Dte::class)->selectedEnvironment() === 'production';

                if (
                    $esProduccion &&
                    ($responseSelloData->estado ?? '') !== 'RECHAZADO' &&
                    !empty($correo) &&
                    filter_var($correo, FILTER_VALIDATE_EMAIL)
                ) {
                    try {
                        $this->procesarCorreoConArchivosAdjuntos(
                            $newData->datainfo->codigoGeneracion,
                            $jsonDTE,
                            $datos,
                            $newData->datainfo->adminnote ?? ''
                        );

                        // Actualizar estado de correo enviado
                        $modelSellos->update($idSelloInterno, [
                            'correoEnviado'      => 1,
                            'fechaCorreoEnviado' => date('Y-m-d H:i:s'),
                            'errorCorreo'        => null,
                        ]);

                        $dataSelloProcesado['correoEnviado'] = true;
                    } catch (\Throwable $e) {
                        log_message('error', 'Error enviando correo DTE ' . $newData->datainfo->codigoGeneracion . ': ' . $e->getMessage());

                        $dataSelloProcesado['correoEnviado'] = false;
                        $dataSelloProcesado['correoError'] = $e->getMessage();
                    }
                } else {

                    // Solo enviar correos en producción y registrar el motivo de la omisión.
                    $modelSellos->update($idSelloInterno, [
                        'correoEnviado'      => 0,
                        'fechaCorreoEnviado' => null,
                        'errorCorreo'        => !$esProduccion
                            ? 'Correo omitido: el sistema no está en modo producción.'
                            : 'Correo omitido: DTE rechazado, correo vacío o correo inválido.',
                    ]);

                    $dataSelloProcesado['correoEnviado'] = false;
                    $dataSelloProcesado['correoOmitido'] = true;
                }

                return $this->response->setJSON($dataSelloProcesado);

            } else {

                $dataSelloProcesado['error'] = true;
                $dataSelloProcesado['message'] = "La Factura ya ha sido procesada anteriormente.";
                $db->transRollback();
                header('Content-Type: application/json; charset=utf-8');
                return $this->response->setJSON($dataSelloProcesado);
            }
        } catch (\Exception $e) {
            $db->transRollback();

            $dataSelloProcesado['error'] = true;
            $dataSelloProcesado['message'] = "Ha ocurrido un problema con el sellado.";
            $dataSelloProcesado['detalleMSG'] = $e->getMessage();
            $dataSelloProcesado['detalleTrace'] = $e->getTrace();
            $dataSelloProcesado['detalleFull'] = $e->getTraceAsString();
            header('Content-Type: application/json; charset=utf-8');
            return $this->response->setJSON($dataSelloProcesado);
        }
    }

    public function facturas_procesadas()
    {
        return $this->render('pages/facturas_procesadas');
    }

    public function facturas_procesadas_data()
    {

        $sellosDteModel = new SellosdteModel();

        $resultados = $sellosDteModel->getDTE();

        $res['error'] = false;
        $res['message'] = 'success get data';
        $res['data'] = $resultados['data'];

        return $this->response->setJSON($res);
    }

    public function mostrarErrorMH(string $factura)
    {

        $sellosDteModel = new SellosdteModel();

        $resultados = $sellosDteModel->obtenerErrorMH($factura);


        return $this->response->setJSON($resultados);
    }

    public function procesadas_archivadas()
    {

        $catTipoDocumentoReceptor = new CatTipoIdentificacionReceptorModel();
        $catTipoInvalidacion = new CatTipoInvalidacionModel();

        $catTipoDocumentoReceptorData = $catTipoDocumentoReceptor->select('codigo, valores')->findAll();
        $catTipoInvalidacionData = $catTipoInvalidacion->select('codigo, valores')->findAll();


        return $this->render('pages/facturas_archivadas', ['catTipoDocumentoReceptor' => $catTipoDocumentoReceptorData, 'catTipoInvalidacion' => $catTipoInvalidacionData]);
    }

    public function facturas_archivadas_data()
    {

        $sellosDteModel = new SellosdteModel();

        $resultados = $sellosDteModel->getDTEArchivo();

        $res['error'] = false;
        $res['message'] = 'success get data';
        $res['data'] = $resultados['data'];

        return $this->response->setJSON($res);
    }

    public function facturas_archivadas_total()
    {
        $sellosDteModel = new SellosdteModel();

        return $sellosDteModel->totalDTEArchivo();
    }

    public function generarPDF($codigoGeneracion, $guardarEnDisco = false, $condiciones = "")
    {
        $ruta = "";
        $model = new SellosdteModel();
        $registro = $model->getJsonDTEByCodigoGeneracion($codigoGeneracion);
        $termsData = "";

        if (empty($condiciones)) {
            $client = \Config\Services::curlrequest();
            $terms = $client->request('GET', "https://erp.grupomegaload.com/api/facturas/termsfactura/" . $codigoGeneracion, ['verify' => true, 'http_errors' => false]);
            if ($terms->getBody() != "") {
                $termsData = json_decode($terms->getBody())->adminnote;
            }
        } else {
            $termsData = $condiciones;
        }

        $estimateNumber = "";
        $client = \Config\Services::curlrequest();
        $estimateNumberData = $client->request('GET', "https://erp.grupomegaload.com/api/facturas/estimate_number/" . $codigoGeneracion, ['verify' => true, 'http_errors' => false]);


        if ($estimateNumberData->getBody() != "") {
            $estimateNumber = json_decode($estimateNumberData->getBody())->estimateNumber;
        }


        if (!$registro || !$registro['jsonDTE']) {
            return $this->response->setStatusCode(404, 'Registro no encontrado o sin contenido JSON');
        }

        $jsonDTE = json_decode($registro['jsonDTE']);
        if ($registro['jsonAnulacion'] != null) {
            $jsonInvalidate = json_decode($registro['jsonAnulacion']);
            $jsonInvalidate = $jsonInvalidate->estado;
        } else {
            $jsonInvalidate = "";
        }

        $jsonSello = json_decode($registro['errorMH']);

        if (!$jsonDTE || !isset($jsonDTE->identificacion->tipoDte)) {
            return $this->response->setStatusCode(400, 'El JSON no tiene un formato válido o falta tipoDte');
        }

        // Manejar diferentes tipos de documentos
        switch ($jsonDTE->identificacion->tipoDte) {
            case "01":
                $ruta = $this->generarFactura($jsonDTE, $jsonSello, $guardarEnDisco, $termsData, $jsonInvalidate, $estimateNumber);
                break;
            case "11":
                $ruta = $this->generarFacturaExportacion($jsonDTE, $jsonSello, $guardarEnDisco, $termsData, $jsonInvalidate, $estimateNumber);
                break;
            case "03":
                $ruta = $this->generarCreditoFiscal($jsonDTE, $jsonSello, $guardarEnDisco, $termsData, $jsonInvalidate, $estimateNumber);
                break;
            case "05":
                $ruta = $this->generarNotaCredito($jsonDTE, $jsonSello, $guardarEnDisco, $termsData, $jsonInvalidate, $estimateNumber);
                break;
            case "06":
                $ruta = $this->generarNotaDebito($jsonDTE, $jsonSello, $guardarEnDisco, $termsData, $jsonInvalidate, $estimateNumber);
                break;
            default:
                return $this->response->setStatusCode(400, 'Tipo de documento no soportado');
        }

        return $ruta;
    }

    private function generarFactura($jsonDTE, $jsonSello, $guardarEnDisco, $condiciones = "", $invalidado = "", $estimateNumber = "")
    {

        $codigoGeneracion = $jsonDTE->identificacion->codigoGeneracion;

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('MEGALOAD, S.A. DE C.V.');
        $pdf->SetTitle('Factura Electrónica');
        $pdf->SetMargins(2, 2, 2, 2);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->AddPage();

        // Coordenadas iniciales
        $x_inicio = 3;  // Margen izquierdo
        $y_inicio = 8;  // Margen superior

        $pdf->Image(FCPATH . 'images/logo_megaload_only_img.png', 35, 5, 20, 20, 'PNG', 'https://www.grupomegaload.com/', '', true, 150, '', false, false, 0, false, false, false);

        $pageWidth = $pdf->GetPageWidth();
        $pageHeight = $pdf->GetPageHeight();
        $margin = 2;

        // Parámetros del rectángulo
        $x = $margin;      // Posición en el eje X
        $y = $margin;      // Posición en el eje Y
        $width = $pageWidth - 2 * $margin; // Ancho del rectángulo
        $height = $pageHeight - 2 * $margin; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');

        $pdf->SetXY(2, 2);
        $pdf->Cell(203, 7, "Ver.1", 0, 0, 'R');

        $pdf->SetXY($x_inicio, $y_inicio += 16);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(85, 7, $jsonDTE->emisor->nombre, 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY($x_inicio, $y_inicio += 4);
        $pdf->Cell(85, 7, $jsonDTE->emisor->direccion->complemento, 0, 0, 'C');
        $pdf->SetXY($x_inicio, $y_inicio + 4);

        $dataSurcursalPuntoVentaTipoEstablecimiento = $this->modelMantenimientos->obtenerSucursalPuntoVentaTipoEstablecimiento($jsonDTE->emisor->codEstable);

        $pdf->Cell(85, 7, $dataSurcursalPuntoVentaTipoEstablecimiento[0]['sucursal'] . ', ' . $dataSurcursalPuntoVentaTipoEstablecimiento[0]['puntoVenta'], 0, 0, 'C');
        $pdf->SetXY($x_inicio, $y_inicio + 8);
        $pdf->Cell(85, 7, $jsonDTE->emisor->correo . ", Tel: " . $jsonDTE->emisor->telefono, 0, 0, 'C');
        //$pdf->SetXY($x_inicio + 50, $y_inicio + 8);
        //$pdf->Cell(40, 7, , 0, 0, 'L');
        $pdf->SetXY($x_inicio + 24, $y_inicio + 12);
        $pdf->Cell(40, 7, 'https://www.grupomegaload.com/', 0, 0, 'C', 0, 'https://www.grupomegaload.com/');

        $pdf->SetXY(115, 8);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(90, 5, 'Documento Tributario Electrónico', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY(115, 12);
        $pdf->Cell(90, 5, 'Factura Electrónica', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(115, 16);
        $pdf->Cell(27, 6, 'Código de generación:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', '7');
        $pdf->Cell(90, 6, $jsonDTE->identificacion->codigoGeneracion, 0, 0, 'L');
        $pdf->SetXY(115, 20);
        $pdf->SetFont('helvetica', '', '7');
        $pdf->Cell(32, 6, 'Número de control del DTE:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', '7');
        $pdf->Cell(90, 6, $jsonDTE->identificacion->numeroControl, 0, 0, 'L');
        $pdf->SetXY(115, 24);
        $pdf->SetFont('helvetica', '', '7');
        $pdf->Cell(23, 6, 'Sello de recepción: ', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', '7');
        $pdf->Cell(90, 6, $jsonSello->selloRecibido, 0, 2, 'L');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(117, 30);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(27, 5, 'Tipo de transmisión', 1, 0, 'C', true);
        $pdf->Cell(27, 5, 'Modelo Facturación', 1, 0, 'C', true);
        $pdf->Cell(30, 5, 'Fecha y hora generación', 1, 0, 'C', true);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetXY(117, 35);
        $pdf->Cell(27, 5, 'Previo', 1, 0, 'C');
        $pdf->Cell(27, 5, 'Normal', 1, 0, 'C');
        $pdf->Cell(30, 5, $jsonSello->fhProcesamiento, 1, 0, 'C');
        $pdf->SetXY(117, 41);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(84, 5, 'Moneda: Dólares', 0, 0, 'C');

        // Parámetros del rectángulo
        $x = 90;      // Posición en el eje X
        $y = 8;      // Posición en el eje Y
        $width = 115;  // Ancho del rectángulo
        $height = 39; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');

        $paramsQr['ambiente'] = $jsonDTE->identificacion->ambiente;
        $paramsQr['codigoGeneracion'] = $jsonDTE->identificacion->codigoGeneracion;
        $paramsQr['fecEmi'] = $jsonDTE->identificacion->fecEmi;

        $dataQr = $this->generarCodigoQR($paramsQr);

        // Agregar la imagen QR
        $pdf->SetXY($x_inicio + 100, $y_inicio); // Ubicamos el QR un poco más a la derecha
        $pdf->Image($dataQr->urlQr, $x_inicio + 88, 9, 25, 25, 'PNG', $dataQr->url);

        $pdf->SetLineWidth(0.02);

        $pdf->Line($x_inicio, $y_inicio += 21, $x_inicio + 202, $pdf->GetY() + 21, []);

        $pdf->SetLineWidth(0.03);
        $pdf->SetXY($x_inicio + 1, $y_inicio += 2);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(102, 5, 'EMISOR', 0, 0, 'C');
        $pdf->Cell(101, 5, 'RECEPTOR', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);
        // Anchos y alturas de celdas
        $cellWidth = 45;   // Ajustado para ocupar el 50% de la página en cada tabla
        $cellHeight = 5;

        // Posición inicial para la primera tabla
        // ------------------- Variables de posición inicial -------------------
        $x_inicio_izq = 10;    // Coordenada X para la tabla izquierda
        $x_inicio_der = 105;   // Coordenada X para la tabla derecha (50% de la página)
        $y_inicio = 56;        // Coordenada Y inicial para ambas tablas (alineadas arriba)

        // Inicializar variables para capturar la altura final de cada tabla
        $y_fin_izq = $y_inicio;
        $y_fin_der = $y_inicio;

        // ------------------- Primera Tabla (Izquierda) -------------------
        $campos1 = [
            "Nombre o razón social:",
            "NIT:",
            "NRC:",
            "Actividad económica:",
            "Dirección:",
            "Número de teléfono:",
            "Correo electrónico:",
            "Nombre Comercial:",
            "Tipo de establecimiento:"
        ];

        $valores1 = [
            $jsonDTE->emisor->nombre,
            $jsonDTE->emisor->nit,
            $jsonDTE->emisor->nrc,
            $jsonDTE->emisor->descActividad,
            $jsonDTE->emisor->direccion->complemento,
            $jsonDTE->emisor->telefono,
            $jsonDTE->emisor->correo,
            $jsonDTE->emisor->nombreComercial,
            $dataSurcursalPuntoVentaTipoEstablecimiento[0]['sucursal']
        ];

        // Generar la tabla izquierda recorriendo cada campo y su valor
        $y_pos_izq = $y_inicio;
        for ($i = 0; $i < count($campos1); $i++) {

            // Calcular altura requerida para cada celda
            $campoHeight = $pdf->getStringHeight($cellWidth, $campos1[$i]);
            $valorHeight = $pdf->getStringHeight($cellWidth, $valores1[$i]);

            // Usar la mayor altura entre las dos para que ambas celdas queden alineadas
            $maxHeight = max($cellHeight, $campoHeight, $valorHeight);

            // Imprimir campo (columna izquierda)
            $pdf->SetXY($x_inicio_izq, $y_pos_izq);
            $pdf->MultiCell($cellWidth, $maxHeight, $campos1[$i], 1, 'L');

            // Imprimir valor (columna derecha)
            $pdf->SetXY($x_inicio_izq + $cellWidth, $y_pos_izq);
            $pdf->MultiCell($cellWidth, $maxHeight, $valores1[$i], 1, 'L');

            // Aumentar la posición Y para la siguiente fila
            $y_pos_izq += $maxHeight;
        }

        // Guardar la altura final de la tabla izquierda
        $y_fin_izq = $y_pos_izq;

        // ------------------- Segunda Tabla (Derecha) -------------------
        $campos2 = [
            "Nombre o razón social",
            "Tipo de doc. de Identificación:",
            "No de doc de Identificación",
            "Correo Electrónico",
            "Nombre Comercial"
        ];

        $valores2 = [
            $jsonDTE->receptor->nombre ?? '-',
            (isset($jsonDTE->receptor->tipoDocumento) && $jsonDTE->receptor->tipoDocumento !== null) ? $jsonDTE->receptor->tipoDocumento : '-',
            (isset($jsonDTE->receptor->numDocumento) && $jsonDTE->receptor->numDocumento !== null) ? $jsonDTE->receptor->numDocumento : '-',
            $jsonDTE->receptor->correo ?? '-',
            $jsonDTE->receptor->nombre ?? '-'
        ];

        // Generar la tabla derecha de forma similar
        $y_pos_der = $y_inicio;
        for ($i = 0; $i < count($campos2); $i++) {

            // Calcular altura para este campo y valor
            $valueHeight = $pdf->getStringHeight($cellWidth, $valores2[$i]);
            $maxHeight = max($cellHeight, $valueHeight);

            // Imprimir campo (columna izquierda)
            $pdf->SetXY($x_inicio_der, $y_pos_der);
            $pdf->MultiCell($cellWidth, $maxHeight, $campos2[$i], 1, 'L');

            // Imprimir valor (columna derecha)
            $pdf->SetXY($x_inicio_der + $cellWidth, $y_pos_der);
            $pdf->MultiCell($cellWidth, $maxHeight, $valores2[$i], 1, 'L');

            // Incrementar la posición Y
            $y_pos_der += $maxHeight;
        }

        // Guardar la altura final de la tabla derecha
        $y_fin_der = $y_pos_der;

        // ------------------- Continuar contenido debajo de la tabla más alta -------------------
        // Obtener la mayor altura entre ambas tablas para ubicar el siguiente bloque
        $y_siguiente = max($y_fin_izq, $y_fin_der) + 2;

        // Establecer posición Y justo después de las tablas
        $pdf->SetXY($x_inicio_izq, $y_siguiente);

        // Imprimir título de la siguiente sección
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(205, $cellHeight, 'VENTA A CUENTA DE TERCEROS', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);

        // Avanzar una línea para continuar con contenido
        $pdf->Ln();

        // Parámetros del rectángulo
        $x = $pdf->GetX() + 8;     // Posición en el eje X
        $y = $pdf->GetY();      // Posición en el eje Y
        $width = 185;  // Ancho del rectángulo
        $height = 10; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');
        $x = $pdf->GetX() + 10;
        $y = $pdf->GetY() + 2;
        $pdf->SetXY($x, $y);
        $pdf->Cell(58, $cellHeight, 'NIT:  - ', 0, 0, 'L');
        $pdf->Cell(123, $cellHeight, 'Nombre, denominación o razón social:  -', 0, 1, 'L');

        $xPos = $pdf->GetX();
        $yPos = $pdf->GetY() + 5;
        $pdf->SetXY($xPos, $yPos);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(205, $cellHeight, 'DOCUMENTOS RELACIONADOS', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Ln();

        // Parámetros del rectángulo
        $x = $pdf->GetX() + 8;     // Posición en el eje X
        $y = $pdf->GetY();      // Posición en el eje Y
        $width = 185;  // Ancho del rectángulo
        $height = 10; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');

        $x = $pdf->GetX() + 8;
        $y = $pdf->GetY() + 2;
        $pdf->SetXY($x, $y);
        $pdf->Cell(30, $cellHeight, 'Tipo de Documento:', 0, 0, 'L');
        $pdf->Cell(30, $cellHeight, '-', 0, 0, 'C');
        $pdf->Cell(28, $cellHeight, 'N° de documento:', 0, 0, 'L');
        $pdf->Cell(34, $cellHeight, '-', 0, 0, 'C');
        $pdf->Cell(30, $cellHeight, 'Fecha del Documento:', 0, 0, 'L');
        $pdf->Cell(33, $cellHeight, '-', 0, 0, 'C');

        $yPos = $pdf->GetY() + 10;
        $pdf->SetXY(0, $yPos);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(205, $cellHeight, 'OTROS DOCUMENTOS ASOCIADOS', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);

        $pdf->Ln();
        // Parámetros del rectángulo
        $x = $pdf->GetX() + 8;     // Posición en el eje X
        $y = $pdf->GetY();      // Posición en el eje Y
        $width = 185;  // Ancho del rectángulo
        $height = 10; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');
        $x = $pdf->GetX() + 10;
        $y = $pdf->GetY() + 2;
        $pdf->SetXY($x, $y);
        $pdf->Cell(58, $cellHeight, 'Identificación documento:  - ', 0, 0, 'L');
        $pdf->Cell(123, $cellHeight, 'Descripción:  -', 0, 1, 'L');

        // Crear un espacio después de las tablas anteriores
        $pdf->Ln(10);

        // Dimensiones para la nueva tabla
        $cellHeight = 8;   // Altura de cada celda

        //Encabezados de la tabla
        $pageWidth = $pdf->GetPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;

        // Definir encabezados y sus anchos (los anchos deben sumar el ancho total de la página)
        $encabezados = [
            ["N°", 10],                       // Número, ancho de 10 unidades
            ["Cantidad", 15],                 // Cantidad, ancho de 15 unidades
            ["Unidad", 12],                   // Unidad, ancho de 15 unidades
            ["Descripción", 65],              // Descripción, ancho de 60 unidades
            ["Precio Unitario", 14],          // Precio Unitario, ancho de 25 unidades
            ["Otros montos no afectados", 20], // Otros montos, ancho de 25 unidades
            ["Descuento por ítem", 16],       // Descuento, ancho de 25 unidades
            ["Ventas no sujetas", 16],        // Ventas no sujetas, ancho de 25 unidades
            ["Ventas Exentas", 16],           // Ventas Exentas, ancho de 25 unidades
            ["Ventas Gravadas", 16]           // Ventas Gravadas, ancho de 30 unidades
        ];

        // Crear el encabezado de la tabla con un fondo gris claro
        $pdf->SetFillColor(200, 200, 200); // Color de fondo para el encabezado
        $pdf->SetTextColor(0, 0, 0);       // Color de texto

        $xPos = $pdf->GetX();
        $y_pos = $pdf->GetY();
        $pdf->SetFont('helvetica', 'B', 7);
        foreach ($encabezados as $header) {
            $pdf->SetXY($xPos + 2, $y_pos);
            $pdf->MultiCell($header[1], $cellHeight, $header[0], 1, 'C', true); // Ancho de columna individual
            $xPos += $header[1];
        }
        $pdf->SetFont('helvetica', '', 7);

        // Resetear el color de fondo para las siguientes filas
        $pdf->SetFillColor(255, 255, 255); // Blanco para las filas de datos

        $xPos = $pdf->GetX();
        $yPos = $pdf->GetY();

        $cellHeight = 5;

        $totalIva = 0;
        $sumVentasGravadas = 0;
        $sumVentasExentas = 0;
        // Agregar las filas dinámicas
        $contador = 0;

        foreach ($jsonDTE->cuerpoDocumento as $index => $objeto) {
            $totalIva += $objeto->ivaItem;
            $sumVentasGravadas += $objeto->ventaGravada;
            $sumVentasExentas += $objeto->ventaExenta;

            $unidad = $this->modelMantenimientos->obtenerUnidad($objeto->uniMedida);

            // Calcular altura de la descripción
            $descripcion = $objeto->descripcion;
            $anchoDescripcion = $encabezados[3][1];
            $alturaDescripcion = $pdf->getStringHeight($anchoDescripcion, $descripcion);

            // Obtener altura final de fila (la más alta)
            $alturaFila = max($alturaDescripcion, $cellHeight);

            // Guardar posición inicial
            $x = $xPos + 2;
            $y = $yPos;

            $pdf->SetXY($x, $y);
            $pdf->Cell($encabezados[0][1], $alturaFila, $objeto->numItem, 1, 0, 'C');
            $pdf->Cell($encabezados[1][1], $alturaFila, $objeto->cantidad, 1, 0, 'C');
            $pdf->Cell($encabezados[2][1], $alturaFila, $unidad[0]['Unidad'], 1, 0, 'C');

            // Descripción con MultiCell (reseteamos X después)
            $xDescripcion = $pdf->GetX();
            $yDescripcion = $pdf->GetY();
            $pdf->MultiCell($anchoDescripcion, $cellHeight, $descripcion, 1, 'L', false, 0);

            $pdf->SetXY($xDescripcion + $anchoDescripcion, $y); // mover a la derecha

            $pdf->Cell($encabezados[4][1], $alturaFila, number_format($objeto->precioUni, 2, '.', ','), 1, 0, 'R');
            $pdf->Cell($encabezados[5][1], $alturaFila, '0.00', 1, 0, 'R');
            $pdf->Cell($encabezados[6][1], $alturaFila, ($objeto->montoDescu == "0") ? '0.00' : number_format($objeto->montoDescu, 2, '.', ','), 1, 0, 'R');
            $pdf->Cell($encabezados[7][1], $alturaFila, '0.00', 1, 0, 'R');
            $pdf->Cell($encabezados[8][1], $alturaFila, ($objeto->ventaExenta == "0") ? '0.00' : number_format($objeto->ventaExenta, 2, '.', ','), 1, 0, 'R');
            $pdf->Cell($encabezados[9][1], $alturaFila, ($objeto->ventaGravada == "0") ? '0.00' : number_format($objeto->ventaGravada, 2, '.', ','), 1, 1, 'R');

            $yPos += $alturaFila;
        }


        $xPos = $pdf->GetX();
        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 2, $yPos);
        $pdf->Cell(10, $cellHeight, '', 1, 0, 'C');
        $pdf->Cell(15, $cellHeight, '', 1, 0, 'C');
        $pdf->Cell(12, $cellHeight, '', 1, 0, 'C');
        $pdf->Cell(65, $cellHeight, '', 1, 0, 'C');
        $pdf->Cell(14, $cellHeight, '', 1, 0, 'C');
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(36, $cellHeight, 'Suma de Ventas:', 1, 0, 'R');
        $pdf->Cell(16, $cellHeight, '0.00', 1, 0, 'R');
        $pdf->Cell(16, $cellHeight, number_format($sumVentasExentas, 2, '.', ','), 1, 0, 'R');
        $pdf->Cell(16, $cellHeight, number_format($sumVentasGravadas, 2, '.', ','), 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);

        // Posición inicial y altura de cada fila
        $xPos = 4;
        $yPos = $pdf->GetY() + 5;
        $totalRows = 10; // Número total de filas que abarcará la celda combinada

        // Crear celda combinada verticalmente en la primera columna
        $pdf->SetXY($xPos, $yPos); // Establece la posición de inicio

        $texto = "Cotización: " . $estimateNumber . "\nNota: " . $condiciones;
        $pdf->MultiCell(102, $cellHeight * $totalRows, $texto, 1, 'L', 0, 0);

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos); // Ajusta la posición en X después de la celda combinada
        // Celdas para el label y el valor de cada fila
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Suma Total de Operaciones:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, ($sumVentasGravadas != 0) ? number_format($sumVentasGravadas, 2, '.', ',') : number_format($sumVentasExentas, 2, '.', ','), 1, 1, 'R'); // Último parámetro en 1 para moverse a la siguiente línea
        $yPos = $pdf->GetY();

        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Monto global Desc., Rebajas y otros a ventas no sujetas:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, '0.00', 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Monto global Desc., Rebajas y otros a ventas exentas:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, number_format($jsonDTE->resumen->descuExenta, 2, '.', ','), 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Monto global Desc., Rebajas y otros a ventas gravadas:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, '0.00', 1, 1, 'R');


        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Sub-Total:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, number_format($jsonDTE->resumen->subTotal, 2, '.', ','), 1, 1, 'R');


        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'IVA Retenido:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, number_format((isset($jsonDTE->resumen->ivaRete1) ? $jsonDTE->resumen->ivaRete1 : $jsonDTE->resumen->totalIva), 2, '.', ','), 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Retención Renta:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, '0.00', 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Monto Total de la Operación:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, number_format($jsonDTE->resumen->montoTotalOperacion, 2, '.', ','), 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Total Otros Montos No Afectos:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, '0.00', 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Total a Pagar:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, number_format($jsonDTE->resumen->totalPagar, 2, '.', ','), 1, 1, 'R');

        $pdf->Ln();
        // Parámetros del rectángulo
        $x = $pdf->GetX() + 2;     // Posición en el eje X
        $y = $pdf->GetY();      // Posición en el eje Y
        $width = 200;  // Ancho del rectángulo
        $height = 10; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');
        $x = $pdf->GetX() + 2;
        $y = $pdf->GetY();
        $pdf->SetXY($x, $y);
        $pdf->Cell(200, $cellHeight, 'Valor en Letras: ' . $jsonDTE->resumen->totalLetras, 0, 0, 'L');
        $x = 4;
        $y = $pdf->GetY() + 5;
        $pdf->SetXY($x, $y);

        $dataObtenerCondicionOperacion = $this->modelMantenimientos->obtenercondicionOperacion($jsonDTE->resumen->condicionOperacion);

        $pdf->Cell(100, $cellHeight, 'Condición de la Operación:  ' . $dataObtenerCondicionOperacion[0]['condicionOperacion'], 0, 0, 'L');
        $pdf->MultiCell(100, $cellHeight, 'Observaciones:  -', 0, 1, 'L');

        $pdf->Ln();
        // Parámetros del rectángulo
        $x = $pdf->GetX() + 2;     // Posición en el eje X
        $y = $pdf->GetY();      // Posición en el eje Y
        $width = 200;  // Ancho del rectángulo
        $height = 10; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');
        $x = $pdf->GetX() + 2;
        $y = $pdf->GetY();
        $pdf->SetXY($x, $y);
        $pdf->Cell(100, $cellHeight, 'Responsable por parte del Emisor:  - ', 0, 0, 'L');
        $pdf->Cell(100, $cellHeight, 'N° de Documento:  - ', 0, 0, 'L');
        $x = 4;
        $y = $pdf->GetY() + 5;
        $pdf->SetXY($x, $y);
        $pdf->Cell(100, $cellHeight, 'Responsable por parte del Receptor:  -', 0, 0, 'L');
        $pdf->Cell(100, $cellHeight, 'N° de Documento:  -', 0, 1, 'L');

        if (!empty($invalidado)) {
            $pageCount = $pdf->getNumPages();
            for ($i = 1; $i <= $pageCount; $i++) {
                $pdf->setPage($i);
                $pdf->StartTransform();
                $pdf->SetTextColor(255, 0, 0);
                $pdf->SetAlpha(0.2);
                $centerX = $pdf->GetPageWidth() / 2;
                $centerY = $pdf->GetPageHeight() / 2;
                $pdf->Rotate(45, $centerX, $centerY);
                $pdf->SetFont('helvetica', 'B', 60);
                $pdf->Text($centerX - 60, $centerY - 10, 'ANULADO');
                $pdf->StopTransform();
                $pdf->SetAlpha(1);
                $pdf->SetTextColor(0, 0, 0);
            }
        }

        if ($guardarEnDisco) {
            $nombreArchivoPDF = $codigoGeneracion . '.pdf';
            $rutaArchivoPDF = WRITEPATH . 'uploads/' . $nombreArchivoPDF;

            $pdf->Output($rutaArchivoPDF, 'F');  // Guardar temporalmente en 'uploads'
            return $rutaArchivoPDF;  // Retornar la ruta para su uso posterior
        } else {
            $this->response->setHeader('Content-Type', 'application/pdf');
            $pdf->Output('factura_electronica_' . $codigoGeneracion . '.pdf', 'I');
        }

        /*if (file_exists($dataQr->urlQ)) {
            unlink($dataQr->urlQ);
        }*/
    }

    private function generarFacturaExportacion($jsonDTE, $jsonSello, $guardarEnDisco = true, $condiciones = "", $invalidado = "", $estimateNumber = "")
    {

        $codigoGeneracion = $jsonDTE->identificacion->codigoGeneracion;

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('MEGALOAD, S.A. DE C.V.');
        $pdf->SetTitle('Factura Electrónica');
        $pdf->SetMargins(2, 2, 2, 2);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->AddPage();

        // Coordenadas iniciales
        $x_inicio = 3;  // Margen izquierdo
        $y_inicio = 8;  // Margen superior

        $pdf->Image(FCPATH . 'images/logo_megaload_only_img.png', 35, 5, 20, 20, 'PNG', 'https://www.grupomegaload.com/', '', true, 150, '', false, false, 0, false, false, false);

        $pageWidth = $pdf->GetPageWidth();
        $pageHeight = $pdf->GetPageHeight();
        $margin = 2;

        // Parámetros del rectángulo
        $x = $margin;      // Posición en el eje X
        $y = $margin;      // Posición en el eje Y
        $width = $pageWidth - 2 * $margin; // Ancho del rectángulo
        $height = $pageHeight - 2 * $margin; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');

        $pdf->SetXY(2, 2);
        $pdf->Cell(203, 7, "Ver." . $jsonSello->version, 0, 0, 'R');

        $pdf->SetXY($x_inicio, $y_inicio += 16);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(85, 7, $jsonDTE->emisor->nombre, 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY($x_inicio, $y_inicio += 4);
        $pdf->Cell(85, 7, $jsonDTE->emisor->direccion->complemento, 0, 0, 'C');
        $pdf->SetXY($x_inicio, $y_inicio + 4);

        $dataSurcursalPuntoVentaTipoEstablecimiento = $this->modelMantenimientos->obtenerSucursalPuntoVentaTipoEstablecimiento($jsonDTE->emisor->codEstable);

        $pdf->Cell(85, 7, $dataSurcursalPuntoVentaTipoEstablecimiento[0]['sucursal'] . ', ' . $dataSurcursalPuntoVentaTipoEstablecimiento[0]['puntoVenta'], 0, 0, 'C');
        $pdf->SetXY($x_inicio, $y_inicio + 8);
        $pdf->Cell(85, 7, $jsonDTE->emisor->correo . ", Tel: " . $jsonDTE->emisor->telefono, 0, 0, 'C');
        //$pdf->SetXY($x_inicio + 50, $y_inicio + 8);
        //$pdf->Cell(40, 7, , 0, 0, 'L');
        $pdf->SetXY($x_inicio + 24, $y_inicio + 12);
        $pdf->Cell(40, 7, 'https://www.grupomegaload.com/', 0, 0, 'C', 0, 'https://www.grupomegaload.com/');

        $pdf->SetXY(115, 8);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(90, 5, 'Documento Tributario Electrónico', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY(115, 12);
        $pdf->Cell(90, 5, 'Facturas de exportación', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(115, 16);
        $pdf->Cell(27, 6, 'Código de generación:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', '7');
        $pdf->Cell(90, 6, $jsonDTE->identificacion->codigoGeneracion, 0, 0, 'L');
        $pdf->SetXY(115, 20);
        $pdf->SetFont('helvetica', '', '7');
        $pdf->Cell(32, 6, 'Número de control del DTE:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', '7');
        $pdf->Cell(90, 6, $jsonDTE->identificacion->numeroControl, 0, 0, 'L');
        $pdf->SetXY(115, 24);
        $pdf->SetFont('helvetica', '', '7');
        $pdf->Cell(23, 6, 'Sello de recepción: ', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', '7');
        $pdf->Cell(90, 6, $jsonSello->selloRecibido, 0, 2, 'L');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(117, 30);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(27, 5, 'Tipo de transmisión', 1, 0, 'C', true);
        $pdf->Cell(27, 5, 'Modelo Facturación', 1, 0, 'C', true);
        $pdf->Cell(30, 5, 'Fecha y hora generación', 1, 0, 'C', true);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetXY(117, 35);
        $pdf->Cell(27, 5, 'Previo', 1, 0, 'C');
        $pdf->Cell(27, 5, 'Normal', 1, 0, 'C');
        $pdf->Cell(30, 5, $jsonSello->fhProcesamiento, 1, 0, 'C');
        $pdf->SetXY(117, 41);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(84, 5, 'Moneda: Dolares', 0, 0, 'C');

        // Parámetros del rectángulo
        $x = 90;      // Posición en el eje X
        $y = 8;      // Posición en el eje Y
        $width = 115;  // Ancho del rectángulo
        $height = 39; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');

        $paramsQr['ambiente'] = $jsonDTE->identificacion->ambiente;
        $paramsQr['codigoGeneracion'] = $jsonDTE->identificacion->codigoGeneracion;
        $paramsQr['fecEmi'] = $jsonDTE->identificacion->fecEmi;

        $dataQr = $this->generarCodigoQR($paramsQr);

        // Agregar la imagen QR
        $pdf->SetXY($x_inicio + 100, $y_inicio); // Ubicamos el QR un poco más a la derecha
        $pdf->Image($dataQr->urlQr, $x_inicio + 88, 9, 25, 25, 'PNG', $dataQr->url);

        $pdf->SetLineWidth(0.5);

        $pdf->Line($x_inicio, $y_inicio += 21, $x_inicio + 202, $pdf->GetY() + 21, []);

        $pdf->SetLineWidth(0.1);
        $pdf->SetXY($x_inicio + 1, $y_inicio += 2);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(102, 5, 'EXPORTADOR (EMISOR)', 0, 0, 'C');
        $pdf->Cell(101, 5, 'RECEPTOR', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);
        // Anchos y alturas de celdas
        $cellWidth = 45;   // Ajustado para ocupar el 50% de la página en cada tabla
        $cellHeight = 5;

        // Posición inicial para la primera tabla
        $x_inicio_izq = 10;    // Coordenada X para la tabla izquierda
        $x_inicio_der = 105;   // Coordenada X para la tabla derecha (50% de la página)
        $y_inicio = 56;        // Coordenada Y para ambas tablas (al nivel superior de la página)

        // ------------------- Primera Tabla (Izquierda) -------------------
        $campos1 = [
            "Nombre o razón social:",
            "NIT:",
            "NRC:",
            "Actividad económica:",
            "Dirección:",
            "Número de teléfono:",
            "Correo electrónico:",
            "Nombre Comercial:",
            "Tipo de establecimiento:",
            "Recinto fiscal:",
            "Regimen de exportación:"
        ];

        $valores1 = [
            $jsonDTE->emisor->nombre,
            $jsonDTE->emisor->nit,
            $jsonDTE->emisor->nrc,
            $jsonDTE->emisor->descActividad,
            $jsonDTE->emisor->direccion->complemento,
            $jsonDTE->emisor->telefono,
            $jsonDTE->emisor->correo,
            $jsonDTE->emisor->nombreComercial,
            $dataSurcursalPuntoVentaTipoEstablecimiento[0]['sucursal'],
            $jsonDTE->emisor->recintoFiscal,
            $jsonDTE->emisor->regimen
        ];

        // Generar la primera tabla
        $y_pos = $y_inicio;  // Reiniciamos Y para cada tabla
        for ($i = 0; $i < count($campos1); $i++) {
            // Calcular la altura requerida para cada celda
            $campoHeight = $pdf->getStringHeight($cellWidth, $campos1[$i]);
            $valorHeight = $pdf->getStringHeight($cellWidth, $valores1[$i]);

            // Asegurarnos de que ambas celdas tengan la misma altura
            $maxHeight = max($cellHeight, $campoHeight, $valorHeight);

            // Imprimir el campo (columna izquierda)
            $pdf->SetXY($x_inicio_izq, $y_pos);
            $pdf->MultiCell($cellWidth, $maxHeight, $campos1[$i], 1, 'L');

            // Imprimir el valor (columna derecha)
            $pdf->SetXY($x_inicio_izq + $cellWidth, $y_pos);
            $pdf->MultiCell($cellWidth, $maxHeight, $valores1[$i], 1, 'L');

            // Ajustar la posición Y para la siguiente fila
            $y_pos += $maxHeight;
        }

        // ------------------- Segunda Tabla (Derecha) -------------------
        $campos2 = [
            "Nombre o razón social",
            "No de doc de Identificación:",
            "Actividad Económica:",
            "País destino",
            "Dirección:",
            "Nombre Comercial"
        ];

        $valores2 = [
            $jsonDTE->receptor->nombre ?? '-',
            (isset($jsonDTE->receptor->numDocumento) && $jsonDTE->receptor->numDocumento !== null) ? $jsonDTE->receptor->numDocumento : '-',
            $jsonDTE->receptor->descActividad ?? '-',
            $jsonDTE->receptor->nombrePais ?? '-',
            $jsonDTE->receptor->complemento ?? '-',
            $jsonDTE->receptor->nombreComercial ?? '-'
        ];

        // Generar la segunda tabla
        $y_pos = $y_inicio;  // Reiniciamos Y para la segunda tabla
        for ($i = 0; $i < count($campos2); $i++) {
            // Calcular la altura requerida para el valor
            $valueHeight = $pdf->getStringHeight($cellWidth, $valores2[$i]);

            // Asegurarnos de que ambas celdas tengan la misma altura
            $maxHeight = max($cellHeight, $valueHeight);

            // Imprimir el campo (columna izquierda)
            $pdf->SetXY($x_inicio_der, $y_pos);
            $pdf->MultiCell($cellWidth, $maxHeight, $campos2[$i], 1, 'L');

            // Imprimir el valor (columna derecha)
            $pdf->SetXY($x_inicio_der + $cellWidth, $y_pos);
            $pdf->MultiCell($cellWidth, $maxHeight, $valores2[$i], 1, 'L');

            // Ajustar la posición Y para la siguiente fila
            $y_pos += $maxHeight;
        }

        $xPos = $pdf->GetX();
        $yPos = $pdf->GetY() + 25;
        $pdf->SetXY($xPos, $yPos);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(205, $cellHeight, 'DOCUMENTOS ASOCIADOS', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);

        $pdf->Ln();

        // Parámetros del rectángulo
        $x = $pdf->GetX() + 8;     // Posición en el eje X
        $y = $pdf->GetY();         // Posición en el eje Y
        $width = 190;              // Ancho del rectángulo
        $radius = 1;               // Radio de las esquinas redondeadas

        // Definir encabezados y valores
        $headers = [
            "Identificación del doc. Asociado:",
            "Descripción de doc. asociado:",
            "Nombre del Conductor:",
            "N°de Identificación:",
            "Modo de Transporte:",
            "N° ID transporte:"
        ];

        $values = [
            "",
            "",
            "",
            "",
            "",
            ""
        ];

        // Ancho de cada columna
        $colWidth = 31.6;
        $lastColWidth = $colWidth - 4;

        // Calcular la altura dinámica total de las filas
        $totalHeight = 0;
        $rowHeights = []; // Para almacenar la altura de cada fila (encabezado + valor)

        for ($i = 0; $i < count($headers); $i++) {
            // Calcular la altura requerida para el encabezado y el valor
            $headerHeight = $pdf->getStringHeight($colWidth, $headers[$i]);
            $valueHeight = $pdf->getStringHeight($colWidth, $values[$i]);

            // Determinar la mayor altura entre el encabezado y el valor
            $rowHeight = max($headerHeight, $valueHeight);

            // Guardar la altura de la fila y acumularla
            $rowHeights[] = $rowHeight;
            $totalHeight += $rowHeight;
        }

        // Dibujar el rectángulo adaptado a la altura total
        $pdf->RoundedRect($x, $y, $width, 20, $radius, '1111', 'D');

        // Coordenadas iniciales para la tabla
        $xTable = $x + 2; // Margen interno
        $yTable = $y + 2;

        // Imprimir los encabezados (Primera fila)

        foreach ($headers as $index => $header) {
            $pdf->SetXY($xTable, $yTable);
            // Ajustar el ancho del último encabezado
            $currentColWidth = ($index === count($headers) - 1) ? $lastColWidth : $colWidth;

            // Alinear encabezado al centro vertical
            $pdf->MultiCell($currentColWidth, 8, $header, 0, 'C', false);

            // Ajustar posición X
            $xTable += $currentColWidth;
        }

        // Reiniciar coordenadas X para la segunda fila (valores)
        $xTable = $x + 2;
        $yTable += max($rowHeights) + 1;

        // Imprimir los valores (Segunda fila)

        foreach ($values as $index => $value) {
            $currentColWidth = ($index === count($values) - 1) ? $lastColWidth : $colWidth;
            $pdf->SetXY($xTable, $yTable);
            $pdf->MultiCell($currentColWidth, 8, $value, 0, 'C', false);
            $xTable += $currentColWidth;
        }

        $xPos = $pdf->GetX();
        $yPos = $pdf->GetY() + 5;
        $pdf->SetXY($xPos, $yPos);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(205, $cellHeight, 'DOCUMENTOS ASOCIADOS', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);

        $pdf->Ln();

        // Parámetros del rectángulo
        $x = $pdf->GetX() + 8;     // Posición en el eje X
        $y = $pdf->GetY();         // Posición en el eje Y
        $width = 190;              // Ancho del rectángulo
        $radius = 1;

        $pdf->RoundedRect($x, $y, $width, 12, $radius, '1111', 'D');

        // Coordenadas iniciales para la tabla
        $xTable = $x + 2; // Margen interno
        $yTable = $y + 2;

        $pdf->SetXY($xTable, $yTable);
        $pdf->MultiCell(20, 8, 'NIT', 0, 'C', false);
        $pdf->SetXY($xTable + 20, $yTable);
        $pdf->MultiCell(35, 8, '', 0, 'C', false);
        $pdf->SetXY($xTable + 55, $yTable);
        $pdf->MultiCell(50, 8, 'Nombre, denominación o razón social:', 0, 'C', false);
        $pdf->SetXY($xTable + 105, $yTable);
        $pdf->MultiCell(80, 8, '', 0, 'C', false);

        $pdf->Ln();

        // Dimensiones para la nueva tabla
        $cellHeight = 8;   // Altura de cada celda

        //Encabezados de la tabla
        $pageWidth = $pdf->GetPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;

        // Definir encabezados y sus anchos (los anchos deben sumar el ancho total de la página)
        $encabezados = [
            ["N°", 10],                       // Número, ancho de 10 unidades
            ["Cantidad", 15],                 // Cantidad, ancho de 15 unidades
            ["Unidad", 12],                   // Unidad, ancho de 15 unidades
            ["Descripción", 70],              // Descripción, ancho de 60 unidades
            ["Precio Unitario", 14],          // Precio Unitario, ancho de 25 unidades
            ["Otros montos no afectados", 22], // Otros montos, ancho de 25 unidades
            ["Descuento por ítem", 16],       // Descuento, ancho de 25 unidades
            ["Otros montos no afectos", 25],        // Ventas no sujetas, ancho de 25 unidades
            ["Ventas Afectas", 16],           // Ventas Exentas, ancho de 25 unidades
        ];

        // Crear el encabezado de la tabla con un fondo gris claro
        $pdf->SetFillColor(200, 200, 200); // Color de fondo para el encabezado
        $pdf->SetTextColor(0, 0, 0);       // Color de texto

        $xPos = $pdf->GetX();
        $y_pos = $pdf->GetY();
        $pdf->SetFont('helvetica', 'B', 7);
        foreach ($encabezados as $header) {
            $pdf->SetXY($xPos + 2, $y_pos);
            $pdf->MultiCell($header[1], $cellHeight, $header[0], 1, 'C', true); // Ancho de columna individual
            $xPos += $header[1];
        }
        $pdf->SetFont('helvetica', '', 7);

        // Resetear el color de fondo para las siguientes filas
        $pdf->SetFillColor(255, 255, 255); // Blanco para las filas de datos

        $xPos = $pdf->GetX();
        $yPos = $pdf->GetY();

        $cellHeight = 5;

        $totalIva = 0;
        $sumVentasGravadas = 0;
        // Agregar las filas dinámicas
        $contador = 0;

        foreach ($jsonDTE->cuerpoDocumento as $index => $objeto) {
            $contador = 0;
            $sumVentasGravadas += $objeto->ventaGravada;
            $pdf->SetXY($xPos + 2, $yPos);
            $pdf->Cell($encabezados[0][1], $cellHeight, $objeto->numItem, 1, 0, 'C');
            $pdf->Cell($encabezados[1][1], $cellHeight, $objeto->cantidad, 1, 0, 'C');

            $unidad = $this->modelMantenimientos->obtenerUnidad($objeto->uniMedida);

            $pdf->Cell($encabezados[2][1], $cellHeight, $unidad[0]['Unidad'], 1, 0, 'C');
            $pdf->Cell($encabezados[3][1], $cellHeight, $objeto->descripcion, 1, 0, 'C');
            $pdf->Cell($encabezados[4][1], $cellHeight, number_format($objeto->precioUni, 2, '.', ','), 1, 0, 'R');
            $pdf->Cell($encabezados[5][1], $cellHeight, '0.00', 1, 0, 'R');
            $pdf->Cell($encabezados[6][1], $cellHeight, ($objeto->montoDescu == "0") ? '0.00' : $objeto->montoDescu, 1, 0, 'R');
            $pdf->Cell($encabezados[7][1], $cellHeight, '0.00', 1, 0, 'R');
            $pdf->Cell($encabezados[8][1], $cellHeight, ($objeto->ventaGravada == "0") ? '0.00' : $objeto->ventaGravada, 1, 1, 'R');
            $yPos += 5;
        }

        $xPos = $pdf->GetX();
        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 2, $yPos);
        $pdf->Cell(10, $cellHeight, '', 1, 0, 'C');
        $pdf->Cell(15, $cellHeight, '', 1, 0, 'C');
        $pdf->Cell(12, $cellHeight, '', 1, 0, 'C');
        $pdf->Cell(70, $cellHeight, '', 1, 0, 'C');
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(14, $cellHeight, '', 1, 0, 'R');
        $pdf->Cell(22, $cellHeight, '0.00', 1, 0, 'R');
        $pdf->Cell(16, $cellHeight, '0.00', 1, 0, 'R');
        $pdf->Cell(25, $cellHeight, '0.00', 1, 0, 'R');
        $pdf->Cell(16, $cellHeight, $sumVentasGravadas, 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);

        // Posición inicial y altura de cada fila
        $xPos = 4;
        $yPos = $pdf->GetY() + 5;
        $totalRows = 7; // Número total de filas que abarcará la celda combinada

        // Crear celda combinada verticalmente en la primera columna
        $pdf->SetXY($xPos, $yPos); // Establece la posición de inicio

        $texto = "Cotización: " . $estimateNumber . "\nNota: " . $condiciones;
        $pdf->MultiCell(102, $cellHeight * $totalRows, $texto, 1, 'L', 0, 0);

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 107, $yPos); // Ajusta la posición en X después de la celda combinada
        // Celdas para el label y el valor de cada fila
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(77, $cellHeight, 'Suma Total de Operaciones:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, $sumVentasGravadas, 1, 1, 'R'); // Último parámetro en 1 para moverse a la siguiente línea

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 107, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(77, $cellHeight, 'Monto global Desc., Rebajas y otros a ventas no sujetas:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, 0, 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 107, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(77, $cellHeight, 'Seguro:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, 0, 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 107, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(77, $cellHeight, 'Flete:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, 0, 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 107, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(77, $cellHeight, 'Monto Total de la Operación:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, $sumVentasGravadas, 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 107, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(77, $cellHeight, 'Total Otros Montos No Afectos:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, 0, 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 107, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(77, $cellHeight, 'Total General:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, $sumVentasGravadas, 1, 1, 'R');

        $pdf->Ln();
        // Parámetros del rectángulo
        $x = $pdf->GetX();     // Posición en el eje X
        $y = $pdf->GetY();      // Posición en el eje Y
        $width = 200;  // Ancho del rectángulo
        $height = 20; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');
        $x = $pdf->GetX() + 2;
        $y = $pdf->GetY();
        $pdf->SetXY($x, $y);
        $pdf->Cell(200, $cellHeight, 'Valor en Letras: ' . $jsonDTE->resumen->totalLetras, 0, 0, 'L');
        $x = 4;
        $y = $pdf->GetY() + 5;
        $pdf->SetXY($x, $y);

        $dataObtenerCondicionOperacion = $this->modelMantenimientos->obtenercondicionOperacion($jsonDTE->resumen->condicionOperacion);

        $pdf->Cell(100, $cellHeight, 'Condición de la Operación:  ' . $dataObtenerCondicionOperacion[0]['condicionOperacion'], 0, 0, 'L');
        $pdf->SetXY($x, $y + 6);
        $pdf->MultiCell(100, $cellHeight, 'Descripción Incoterms::  -', 0, 1, 'L');
        $pdf->SetXY($x, $y + 10);
        $pdf->MultiCell(100, $cellHeight, 'Observaciones:  -', 0, 1, 'L');

        if (!empty($invalidado)) {
            $pageCount = $pdf->getNumPages();
            for ($i = 1; $i <= $pageCount; $i++) {
                $pdf->setPage($i);
                $pdf->StartTransform();
                $pdf->SetTextColor(255, 0, 0);
                $pdf->SetAlpha(0.2);
                $centerX = $pdf->GetPageWidth() / 2;
                $centerY = $pdf->GetPageHeight() / 2;
                $pdf->Rotate(45, $centerX, $centerY);
                $pdf->SetFont('helvetica', 'B', 60);
                $pdf->Text($centerX - 60, $centerY - 10, 'ANULADO');
                $pdf->StopTransform();
                $pdf->SetAlpha(1);
                $pdf->SetTextColor(0, 0, 0);
            }
        }


        if ($guardarEnDisco) {
            $nombreArchivoPDF = $codigoGeneracion . '.pdf';
            $rutaArchivoPDF = WRITEPATH . 'uploads/' . $nombreArchivoPDF;

            $pdf->Output($rutaArchivoPDF, 'F');  // Guardar temporalmente en 'uploads'
            return $rutaArchivoPDF;  // Retornar la ruta para su uso posterior
        } else {
            $this->response->setHeader('Content-Type', 'application/pdf');
            $pdf->Output('factura_electronica_' . $codigoGeneracion . '.pdf', 'I');
        }

        /*if (file_exists($dataQr->urlQ)) {
            unlink($dataQr->urlQ);
        }*/
    }

    private function generarCreditoFiscal($jsonDTE, $jsonSello, $guardarEnDisco = true, $condiciones = "", $invalidado = "", $estimateNumber = "")
    {

        $codigoGeneracion = $jsonDTE->identificacion->codigoGeneracion;

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('MEGALOAD, S.A. DE C.V.');
        $pdf->SetTitle('Factura Electrónica');
        $pdf->SetMargins(2, 2, 2, 2);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->AddPage();

        // Coordenadas iniciales
        $x_inicio = 3;  // Margen izquierdo
        $y_inicio = 8;  // Margen superior

        $pdf->Image(FCPATH . 'images/logo_megaload_only_img.png', 35, 5, 20, 20, 'PNG', 'https://www.grupomegaload.com/', '', true, 150, '', false, false, 0, false, false, false);

        $pageWidth = $pdf->GetPageWidth();
        $pageHeight = $pdf->GetPageHeight();
        $margin = 2;

        // Parámetros del rectángulo
        $x = $margin;      // Posición en el eje X
        $y = $margin;      // Posición en el eje Y
        $width = $pageWidth - 2 * $margin; // Ancho del rectángulo
        $height = $pageHeight - 2 * $margin; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');

        $pdf->SetXY(2, 2);
        $pdf->Cell(203, 7, "Ver." . $jsonDTE->identificacion->version, 0, 0, 'R');

        $pdf->SetXY($x_inicio, $y_inicio += 16);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(85, 7, $jsonDTE->emisor->nombre, 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY($x_inicio, $y_inicio += 4);
        $pdf->Cell(85, 7, $jsonDTE->emisor->direccion->complemento, 0, 0, 'C');
        $pdf->SetXY($x_inicio, $y_inicio + 4);

        $dataSurcursalPuntoVentaTipoEstablecimiento = $this->modelMantenimientos->obtenerSucursalPuntoVentaTipoEstablecimiento($jsonDTE->emisor->codEstable);

        $pdf->Cell(85, 7, $dataSurcursalPuntoVentaTipoEstablecimiento[0]['sucursal'] . ', ' . $dataSurcursalPuntoVentaTipoEstablecimiento[0]['puntoVenta'], 0, 0, 'C');
        $pdf->SetXY($x_inicio, $y_inicio + 8);
        $pdf->Cell(85, 7, $jsonDTE->emisor->correo . ", Tel: " . $jsonDTE->emisor->telefono, 0, 0, 'C');
        //$pdf->SetXY($x_inicio + 50, $y_inicio + 8);
        //$pdf->Cell(40, 7, , 0, 0, 'L');
        $pdf->SetXY($x_inicio + 24, $y_inicio + 12);
        $pdf->Cell(40, 7, 'https://www.grupomegaload.com/', 0, 0, 'C', 0, 'https://www.grupomegaload.com/');

        $pdf->SetXY(115, 8);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(90, 5, 'Documento Tributario Electrónico', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY(115, 12);
        $pdf->Cell(90, 5, 'Factura Credito Fiscal', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(115, 16);
        $pdf->Cell(27, 6, 'Código de generación:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', '7');
        $pdf->Cell(90, 6, $jsonDTE->identificacion->codigoGeneracion, 0, 0, 'L');
        $pdf->SetXY(115, 20);
        $pdf->SetFont('helvetica', '', '7');
        $pdf->Cell(32, 6, 'Número de control del DTE:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', '7');
        $pdf->Cell(90, 6, $jsonDTE->identificacion->numeroControl, 0, 0, 'L');
        $pdf->SetXY(115, 24);
        $pdf->SetFont('helvetica', '', '7');
        $pdf->Cell(23, 6, 'Sello de recepción: ', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', '7');
        $pdf->Cell(90, 6, $jsonSello->selloRecibido, 0, 2, 'L');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(117, 30);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(84, 5, 'Fecha de Emisión: ' .  $jsonDTE->identificacion->fecEmi . ' ' . $jsonDTE->identificacion->horEmi , 0, 0, 'L');
        $pdf->SetXY(150, 30);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(51, 5, 'Moneda: Dolares', 0, 0, 'R');
        $pdf->SetXY(117, 35);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(27, 5, 'Tipo de transmisión', 1, 0, 'C', true);
        $pdf->Cell(27, 5, 'Modelo Facturación', 1, 0, 'C', true);
        $pdf->Cell(30, 5, 'Fecha y hora generación', 1, 0, 'C', true);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetXY(117, 40);
        $pdf->Cell(27, 5, 'Previo', 1, 0, 'C');
        $pdf->Cell(27, 5, 'Normal', 1, 0, 'C');
        $pdf->Cell(30, 5, $jsonSello->fhProcesamiento, 1, 0, 'C');


        // Parámetros del rectángulo
        $x = 90;      // Posición en el eje X
        $y = 8;      // Posición en el eje Y
        $width = 115;  // Ancho del rectángulo
        $height = 39; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');

        $paramsQr['ambiente'] = $jsonDTE->identificacion->ambiente;
        $paramsQr['codigoGeneracion'] = $jsonDTE->identificacion->codigoGeneracion;
        $paramsQr['fecEmi'] = $jsonDTE->identificacion->fecEmi;

        $dataQr = $this->generarCodigoQR($paramsQr);

        // Agregar la imagen QR
        $pdf->SetXY($x_inicio + 100, $y_inicio); // Ubicamos el QR un poco más a la derecha
        $pdf->Image($dataQr->urlQr, $x_inicio + 88, 9, 25, 25, 'PNG', $dataQr->url);

        $pdf->SetLineWidth(0.02);

        $pdf->Line($x_inicio, $y_inicio += 21, $x_inicio + 202, $pdf->GetY() + 21, []);

        $pdf->SetLineWidth(0.03);
        $pdf->SetXY($x_inicio + 1, $y_inicio += 2);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(102, 5, 'EMISOR', 0, 0, 'C');
        $pdf->Cell(101, 5, 'RECEPTOR', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);
        // Anchos y alturas de celdas
        $cellWidth = 45;   // Ajustado para ocupar el 50% de la página en cada tabla
        $cellHeight = 5;

        // Posición inicial para la primera tabla
        $x_inicio_izq = 10;    // Coordenada X para la tabla izquierda
        $x_inicio_der = 105;   // Coordenada X para la tabla derecha (50% de la página)
        $y_inicio = 56;        // Coordenada Y para ambas tablas (al nivel superior de la página)

        // ------------------- Primera Tabla (Izquierda) -------------------
        $campos1 = [
            "Nombre o razón social:",
            "NIT:",
            "NRC:",
            "Actividad económica:",
            "Dirección:",
            "Número de teléfono:",
            "Correo electrónico:",
            "Nombre Comercial:",
            "Tipo de establecimiento:"
        ];


        $valores1 = [
            $jsonDTE->emisor->nombre,
            $jsonDTE->emisor->nit,
            $jsonDTE->emisor->nrc,
            $jsonDTE->emisor->descActividad,
            $jsonDTE->emisor->direccion->complemento,
            $jsonDTE->emisor->telefono,
            $jsonDTE->emisor->correo,
            $jsonDTE->emisor->nombreComercial,
            $dataSurcursalPuntoVentaTipoEstablecimiento[0]['sucursal']
        ];

        // Generar la primera tabla
        $y_pos = $y_inicio;  // Reiniciamos Y para cada tabla
        for ($i = 0; $i < count($campos1); $i++) {
            // Calcular la altura requerida para cada celda
            $campoHeight = $pdf->getStringHeight($cellWidth, $campos1[$i]);
            $valorHeight = $pdf->getStringHeight($cellWidth, $valores1[$i]);

            // Asegurarnos de que ambas celdas tengan la misma altura
            $maxHeight = max($cellHeight, $campoHeight, $valorHeight);

            // Imprimir el campo (columna izquierda)
            $pdf->SetXY($x_inicio_izq, $y_pos);
            $pdf->MultiCell($cellWidth, $maxHeight, $campos1[$i], 1, 'L');

            // Imprimir el valor (columna derecha)
            $pdf->SetXY($x_inicio_izq + $cellWidth, $y_pos);
            $pdf->MultiCell($cellWidth, $maxHeight, $valores1[$i], 1, 'L');

            // Ajustar la posición Y para la siguiente fila
            $y_pos += $maxHeight;
        }

        // ------------------- Segunda Tabla (Derecha) -------------------
        $campos2 = [
            "Nombre o razón social:",
            "NIT:",
            "NRC:",
            "Actividad Económica:",
            "Dirección:",
            "Correo electrónico:",
            "Nombre Comercial:"
        ];

        $valores2 = [
            $jsonDTE->receptor->nombre ?? '-',
            (isset($jsonDTE->receptor->nit) && $jsonDTE->receptor->nit !== null) ? $jsonDTE->receptor->nit : '-',
            (isset($jsonDTE->receptor->nrc) && $jsonDTE->receptor->nrc !== null) ? $jsonDTE->receptor->nrc : '-',
            $jsonDTE->receptor->descActividad ?? '-',
            (isset($jsonDTE->receptor->direccion->complemento) && $jsonDTE->receptor->direccion->complemento !== null) ? $jsonDTE->receptor->direccion->complemento : '-',
            $jsonDTE->receptor->correo ?? '-',
            $jsonDTE->receptor->nombreComercial ?? '-'
        ];

        // Generar la segunda tabla
        $y_pos = $y_inicio;  // Reiniciamos Y para la segunda tabla
        for ($i = 0; $i < count($campos2); $i++) {
            // Calcular la altura requerida para el valor
            $valueHeight = $pdf->getStringHeight($cellWidth, $valores2[$i]);

            // Asegurarnos de que ambas celdas tengan la misma altura
            $maxHeight = max($cellHeight, $valueHeight);

            // Imprimir el campo (columna izquierda)
            $pdf->SetXY($x_inicio_der, $y_pos);
            $pdf->MultiCell($cellWidth, $maxHeight, $campos2[$i], 1, 'L');

            // Imprimir el valor (columna derecha)
            $pdf->SetXY($x_inicio_der + $cellWidth, $y_pos);
            $pdf->MultiCell($cellWidth, $maxHeight, $valores2[$i], 1, 'L');

            // Ajustar la posición Y para la siguiente fila
            $y_pos += $maxHeight;
        }

        $xPos = $pdf->GetX();
        $yPos = $pdf->GetY() + 5;
        $pdf->SetXY($xPos, $yPos);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(205, $cellHeight, 'OTROS DOCUMENTOS ASOCIADOS', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);

        $pdf->Ln();

        // Parámetros del rectángulo
        $x = $pdf->GetX() + 8;     // Posición en el eje X
        $y = $pdf->GetY();      // Posición en el eje Y
        $width = 185;  // Ancho del rectángulo
        $height = 10; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');
        $x = $pdf->GetX() + 10;
        $y = $pdf->GetY() + 2;
        $pdf->SetXY($x, $y);
        $pdf->Cell(58, $cellHeight, 'Identificación del documento:  - ', 0, 0, 'L');
        $pdf->Cell(123, $cellHeight, 'Descripción:  -', 0, 1, 'L');

        $xPos = $pdf->GetX();
        $yPos = $pdf->GetY() + 5;
        $pdf->SetXY($xPos, $yPos);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(205, $cellHeight, 'VENTA A CUENTA DE TERCEROS', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Ln();

        // Parámetros del rectángulo
        $x = $pdf->GetX() + 8;     // Posición en el eje X
        $y = $pdf->GetY();      // Posición en el eje Y
        $width = 185;  // Ancho del rectángulo
        $height = 10; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');
        $x = $pdf->GetX() + 10;
        $y = $pdf->GetY() + 2;
        $pdf->SetXY($x, $y);
        $pdf->Cell(58, $cellHeight, 'NIT:  - ', 0, 0, 'L');
        $pdf->Cell(123, $cellHeight, 'Nombre, denominación o razón social:  -', 0, 1, 'L');

        $xPos = $pdf->GetX();
        $yPos = $pdf->GetY() + 5;
        $pdf->SetXY($xPos, $yPos);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(205, $cellHeight, 'DOCUMENTOS RELACIONADOS', 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Ln();

        // Parámetros del rectángulo
        $x = $pdf->GetX() + 8;     // Posición en el eje X
        $y = $pdf->GetY();      // Posición en el eje Y
        $width = 185;  // Ancho del rectángulo
        $height = 10; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');

        $x = $pdf->GetX() + 8;
        $y = $pdf->GetY() + 2;
        $pdf->SetXY($x, $y);
        $pdf->Cell(30, $cellHeight, 'Tipo de Documento:', 0, 0, 'L');
        $pdf->Cell(30, $cellHeight, '-', 0, 0, 'C');
        $pdf->Cell(28, $cellHeight, 'N° de documento:', 0, 0, 'L');
        $pdf->Cell(34, $cellHeight, '-', 0, 0, 'C');
        $pdf->Cell(30, $cellHeight, 'Fecha del Documento:', 0, 0, 'L');
        $pdf->Cell(33, $cellHeight, '-', 0, 0, 'C');

        $pdf->Ln(10);

        // Dimensiones para la nueva tabla
        $cellHeight = 8;   // Altura de cada celda

        //Encabezados de la tabla
        $pageWidth = $pdf->GetPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;

        // Definir encabezados y sus anchos (los anchos deben sumar el ancho total de la página)
        $encabezados = [
            ["N°", 10],                       // Número, ancho de 10 unidades
            ["Cantidad", 15],                 // Cantidad, ancho de 15 unidades
            ["Unidad", 12],                   // Unidad, ancho de 15 unidades
            ["Descripción", 65],              // Descripción, ancho de 60 unidades
            ["Precio Unitario", 14],          // Precio Unitario, ancho de 25 unidades
            ["Descuento por ítem", 16],       // Descuento, ancho de 25 unidades
            ["Otros montos no afectados", 20], // Otros montos, ancho de 25 unidades
            ["Ventas no sujetas", 16],        // Ventas no sujetas, ancho de 25 unidades
            ["Ventas Exentas", 16],           // Ventas Exentas, ancho de 25 unidades
            ["Ventas Gravadas", 16]           // Ventas Gravadas, ancho de 30 unidades
        ];

        // Crear el encabezado de la tabla con un fondo gris claro
        $pdf->SetFillColor(200, 200, 200); // Color de fondo para el encabezado
        $pdf->SetTextColor(0, 0, 0);       // Color de texto

        $xPos = $pdf->GetX();
        $y_pos = $pdf->GetY();
        $pdf->SetFont('helvetica', 'B', 7);
        foreach ($encabezados as $header) {
            $pdf->SetXY($xPos + 2, $y_pos);
            $pdf->MultiCell($header[1], $cellHeight, $header[0], 1, 'C', true); // Ancho de columna individual
            $xPos += $header[1];
        }
        $pdf->SetFont('helvetica', '', 7);

        // Resetear el color de fondo para las siguientes filas
        $pdf->SetFillColor(255, 255, 255); // Blanco para las filas de datos

        $xPos = $pdf->GetX();
        $yPos = $pdf->GetY();

        $cellHeight = 5;

        $totalIva = 0;
        $sumVentasGravadas = 0;
        $sumVentasExentas = 0;
        // Agregar las filas dinámicas
        $contador = 0;

        foreach ($jsonDTE->cuerpoDocumento as $index => $objeto) {
            $contador = 0;

            $sumVentasGravadas += $objeto->ventaGravada;
            $sumVentasExentas += $objeto->ventaExenta;

            $unidad = $this->modelMantenimientos->obtenerUnidad($objeto->uniMedida);

            // Calcular altura de la descripción
            $descripcion = $objeto->descripcion;
            $anchoDescripcion = $encabezados[3][1];
            $alturaDescripcion = $pdf->getStringHeight($anchoDescripcion, $descripcion);

            // Obtener altura final de fila (la más alta)
            $alturaFila = max($alturaDescripcion, $cellHeight);

            // Guardar posición inicial
            $x = $xPos + 2;
            $y = $yPos;

            $pdf->SetXY($x, $y);
            $pdf->Cell($encabezados[0][1], $alturaFila, $objeto->numItem, 1, 0, 'C');
            $pdf->Cell($encabezados[1][1], $alturaFila, $objeto->cantidad, 1, 0, 'C');
            $pdf->Cell($encabezados[2][1], $alturaFila, $unidad[0]['Unidad'], 1, 0, 'C');

            // Descripción con MultiCell (reseteamos X después)
            $xDescripcion = $pdf->GetX();
            $pdf->MultiCell($anchoDescripcion, $cellHeight, $descripcion, 1, 'L', false, 0);

            $pdf->SetXY($xDescripcion + $anchoDescripcion, $y); // mover a la derecha
            $pdf->Cell($encabezados[4][1], $alturaFila, number_format($objeto->precioUni, 2), 1, 0, 'R');
            $pdf->Cell($encabezados[5][1], $alturaFila, ($objeto->montoDescu == "0") ? '0.00' : number_format($objeto->montoDescu, 2), 1, 0, 'R');
            $pdf->Cell($encabezados[6][1], $alturaFila, '0.00', 1, 0, 'R');
            $pdf->Cell($encabezados[7][1], $alturaFila, '0.00', 1, 0, 'R');
            $pdf->Cell($encabezados[8][1], $alturaFila, ($objeto->ventaExenta == "0") ? '0.00' : number_format($objeto->ventaExenta, 2), 1, 0, 'R');
            $pdf->Cell($encabezados[9][1], $alturaFila, ($objeto->ventaGravada == "0") ? '0.00' : number_format($objeto->ventaGravada, 2), 1, 1, 'R');
            $yPos += $alturaFila;
        }

        $xPos = $pdf->GetX();
        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 2, $yPos);
        $pdf->Cell(10, $cellHeight, '', 1, 0, 'C');
        $pdf->Cell(15, $cellHeight, '', 1, 0, 'C');
        $pdf->Cell(12, $cellHeight, '', 1, 0, 'C');
        $pdf->Cell(65, $cellHeight, '', 1, 0, 'C');
        $pdf->Cell(14, $cellHeight, '', 1, 0, 'C');
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(36, $cellHeight, 'SUMA DE VENTAS:', 1, 0, 'R');
        $pdf->Cell(16, $cellHeight, '0.00', 1, 0, 'R');
        $pdf->Cell(16, $cellHeight, number_format($sumVentasExentas, 2), 1, 0, 'R');
        $pdf->Cell(16, $cellHeight, number_format($sumVentasGravadas, 2), 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);

        // Posición inicial y altura de cada fila
        $xPos = 4;
        $yPos = $pdf->GetY() + 5;
        $totalRows = 12; // Número total de filas que abarcará la celda combinada

        // Crear celda combinada verticalmente en la primera columna
        $pdf->SetXY($xPos, $yPos); // Establece la posición de inicio

        $texto = "Cotización: " . $estimateNumber . "\nNota: " . $condiciones;
        $pdf->MultiCell(102, $cellHeight * $totalRows, $texto, 1, 'L', 0, 0);

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos); // Ajusta la posición en X después de la celda combinada
        // Celdas para el label y el valor de cada fila
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Suma Total de Operaciones:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, number_format(($sumVentasGravadas != 0) ? $sumVentasGravadas : $sumVentasExentas, 2), 1, 1, 'R'); // Último parámetro en 1 para moverse a la siguiente línea
        $yPos = $pdf->GetY();

        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Monto global Desc., Rebajas y otros a ventas no sujetas:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, 0, 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Monto global Desc., Rebajas y otros a ventas exentas:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, 0, 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Monto global Desc., Rebajas y otros a ventas gravadas:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, number_format($jsonDTE->resumen->totalDescu, 2), 1, 1, 'R');

        $tributoValor = 0.00;

        if ($jsonDTE->resumen->tributos != null) {
            $tributoValor = $jsonDTE->resumen->tributos[0]->valor;
        }

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Impuesto al Valor Agregado 13%:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, number_format($tributoValor, 2), 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Sub-Total:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, number_format($jsonDTE->resumen->subTotalVentas, 2), 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'IVA Percibido:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, 0.00, 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'IVA Retenido:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, $jsonDTE->resumen->ivaRete1, 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Retención Renta:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, 0.00, 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Monto Total de la Operación:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, number_format($jsonDTE->resumen->montoTotalOperacion, 2), 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Total Otros Montos No Afectos:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, 0.00, 1, 1, 'R');

        $yPos = $pdf->GetY();
        $pdf->SetXY($xPos + 102, $yPos);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(82, $cellHeight, 'Total a Pagar:', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(16, $cellHeight, number_format($jsonDTE->resumen->totalPagar, 2), 1, 1, 'R');

        $pdf->Ln();

        // Parámetros del rectángulo
        $x = $pdf->GetX() + 2;     // Posición en el eje X
        $y = $pdf->GetY();      // Posición en el eje Y
        $width = 200;  // Ancho del rectángulo
        $height = 10; // Alto del rectángulo
        $radius = 1;  // Radio de las esquinas redondeadas

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');
        $x = $pdf->GetX() + 2;
        $y = $pdf->GetY();
        $pdf->SetXY($x, $y);
        $pdf->Cell(200, $cellHeight, 'Valor en Letras: ' . $jsonDTE->resumen->totalLetras, 0, 0, 'L');
        $x = 4;
        $y = $pdf->GetY() + 5;
        $pdf->SetXY($x, $y);


        $dataObtenerCondicionOperacion = $this->modelMantenimientos->obtenercondicionOperacion($jsonDTE->resumen->condicionOperacion);

        $pdf->Cell(100, $cellHeight, 'Condición de la Operación:  ' . $dataObtenerCondicionOperacion[0]['condicionOperacion'], 0, 0, 'L');
        $pdf->MultiCell(100, $cellHeight, 'Observaciones:  -', 0, 1, 'L');

        $pdf->Ln();

        $alturaBloque = 10;
        if ($pdf->GetY() + $alturaBloque > $pdf->getPageHeight() - $pdf->getBreakMargin()) {
            $pdf->AddPage();
        }

// Coordenadas del rectángulo
        $x = $pdf->GetX() + 2;
        $y = $pdf->GetY();
        $width = 200;
        $height = $alturaBloque;
        $radius = 1;

        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'D');

// Contenido dentro del rectángulo
        $x = $pdf->GetX() + 2;
        $y = $pdf->GetY();
        $pdf->SetXY($x, $y);
        $pdf->Cell(100, $cellHeight, 'Responsable por parte del Emisor:  - ', 0, 0, 'L');
        $pdf->Cell(100, $cellHeight, 'N° de Documento:  - ', 0, 0, 'L');

        $x = 4;
        $y = $pdf->GetY() + 5;
        $pdf->SetXY($x, $y);
        $pdf->Cell(100, $cellHeight, 'Responsable por parte del Receptor:  -', 0, 0, 'L');
        $pdf->Cell(100, $cellHeight, 'N° de Documento:  -', 0, 1, 'L');


        if (!empty($invalidado)) {
            $pageCount = $pdf->getNumPages();
            for ($i = 1; $i <= $pageCount; $i++) {
                $pdf->setPage($i);
                $pdf->StartTransform();
                $pdf->SetTextColor(255, 0, 0);
                $pdf->SetAlpha(0.2);
                $centerX = $pdf->GetPageWidth() / 2;
                $centerY = $pdf->GetPageHeight() / 2;
                $pdf->Rotate(45, $centerX, $centerY);
                $pdf->SetFont('helvetica', 'B', 60);
                $pdf->Text($centerX - 60, $centerY - 10, 'ANULADO');
                $pdf->StopTransform();
                $pdf->SetAlpha(1);
                $pdf->SetTextColor(0, 0, 0);
            }
        }


        if ($guardarEnDisco) {
            $nombreArchivoPDF = $codigoGeneracion . '.pdf';
            $rutaArchivoPDF = WRITEPATH . 'uploads/' . $nombreArchivoPDF;

            $pdf->Output($rutaArchivoPDF, 'F');  // Guardar temporalmente en 'uploads'
            return $rutaArchivoPDF;  // Retornar la ruta para su uso posterior
        } else {
            $this->response->setHeader('Content-Type', 'application/pdf');
            $pdf->Output('DTE_' . $codigoGeneracion . '.pdf', 'I');
        }
    }

    public function generarPDFInvalidacion($codigoGeneracion)
    {
        $registro = (new SellosdteModel())->getJsonDTEByCodigoGeneracion($codigoGeneracion);
        if (!$registro) {
            return $this->response->setStatusCode(404)->setBody('DTE no encontrado.');
        }
        $generador = new \App\Libraries\InvalidacionPdf();
        try {
            $evento = $generador->obtenerEvento($registro);
        } catch (\DomainException | \UnexpectedValueException $exception) {
            return $this->response->setStatusCode(409)->setBody($exception->getMessage());
        }
        $pdf = $generador->generar($evento, json_decode($registro['jsonAnulacion']));
        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="Invalidacion_' . $codigoGeneracion . '.pdf"')
            ->setBody($pdf->Output('', 'S'));
    }

    private function generarNotaCredito($jsonDTE, $jsonSello, $guardarEnDisco = true, $condiciones = '', $invalidado = '', $estimateNumber = '')
    {
        return $this->generarNota($jsonDTE, $jsonSello, $guardarEnDisco, $condiciones, $invalidado, $estimateNumber);
    }

    private function generarNotaDebito($jsonDTE, $jsonSello, $guardarEnDisco = true, $condiciones = '', $invalidado = '', $estimateNumber = '')
    {
        return $this->generarNota($jsonDTE, $jsonSello, $guardarEnDisco, $condiciones, $invalidado, $estimateNumber);
    }

    private function generarNota($jsonDTE, $jsonSello, $guardarEnDisco, $condiciones, $invalidado, $estimateNumber)
    {
        $pdf = (new \App\Libraries\NotaPdf())->generar(
            $jsonDTE, $jsonSello, (string) $condiciones, (string) $invalidado, (string) $estimateNumber,
            function ($codigo) {
                $unidad = $this->modelMantenimientos->obtenerUnidad($codigo);
                return $unidad[0]['Unidad'] ?? (string) $codigo;
            }
        );
        $codigoGeneracion = $jsonDTE->identificacion->codigoGeneracion;
        if ($guardarEnDisco) {
            $ruta = WRITEPATH . 'uploads/' . $codigoGeneracion . '.pdf';
            $pdf->Output($ruta, 'F');
            return $ruta;
        }
        return $this->response->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="DTE_' . $codigoGeneracion . '.pdf"')
            ->setBody($pdf->Output('', 'S'));
    }

    function generarCodigoQR($data)
    {

        $url = "https://admin.factura.gob.sv/consultaPublica?ambiente={$data['ambiente']}&codGen={$data['codigoGeneracion']}&fechaEmi={$data['fecEmi']}";

        $builder = new Builder(
            writer: new PngWriter(),
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 0
        );

        $result = $builder->build();

        $directorioQR = WRITEPATH . 'uploads/qrs/';

        if (!is_dir($directorioQR)) {
            mkdir($directorioQR, 0777, true);
        }

        $tempQrPath = $directorioQR . $data['codigoGeneracion'] . '.png';

        $result->saveToFile($tempQrPath);

        $dataUrlQr = new \stdClass();
        $dataUrlQr->urlQr = $tempQrPath;
        $dataUrlQr->url = $url;

        return $dataUrlQr;
    }

    function arrayToObject($array)
    {
        // Verifica si el parámetro es un arreglo
        if (is_array($array)) {
            // Convierte cada elemento del arreglo recursivamente
            return (object)array_map('arrayToObject', $array);
        } else {
            // Si no es un arreglo, regresa el valor original
            return $array;
        }
    }

    public function reenviardte(string $codigoGeneracion)
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {

            $dataSeguridad = $this->modelSeguridad->find();
            $modelSellos = new SellosdteModel();

            $factura = $modelSellos->getByCodigoGeneracion($codigoGeneracion);

            $nuevaFirmaDocumento = $this->actualizarFirma($factura[0]['jsonDTE']);

            $token = $this->validarToken($dataSeguridad);

            $arreglo = explode('-', $factura[0]['numeroControlMH']);

            $idEnvio = intval($arreglo[3]);

            $codigoTipoDTE = $factura[0]['codigoTipoDTE'];
            $version = $factura[0]['version'];
            $codigoGeneracion = $factura[0]['codigoGeneracion'];

            $respSelloDoc = $this->sellarDTE($dataSeguridad, $token, $idEnvio, $codigoTipoDTE, $version, $codigoGeneracion, $nuevaFirmaDocumento);

            $datos = new stdClass();
            $datos->receptor = new stdClass();
            $datos->receptor->nombre = "";
            $datos->receptor->correo = "";
            $datos->dte = new stdClass();
            $datos->dte->fecEmi = "";
            $datos->dte->codigoGeneracion = "";
            $datos->dte->ambiente = "";

            $jsonDTE = json_decode($factura[0]['jsonDTE']);

            $datos->receptor->nombre = $jsonDTE->receptor->nombre;
            $datos->receptor->correo = $jsonDTE->receptor->correo;
            $datos->dte->fecEmi = $jsonDTE->identificacion->fecEmi;
            $datos->dte->codigoGeneracion = $jsonDTE->identificacion->codigoGeneracion;
            $datos->dte->ambiente = $jsonDTE->identificacion->ambiente;

            $updateSelloDTE = [
                'firmaFactura' => $nuevaFirmaDocumento,
                'fechaFirma' => date("Y/m/d"),
                'usuarioFirma' => session()->get('username'),
                'errorMH' => json_encode($respSelloDoc),
                'idEstadoDTE' => 1
            ];

            $modelSellos->update($factura[0]['idsellosDTE'], $updateSelloDTE);

            $db->transCommit();

            $correo = trim((string)($datos->receptor->correo ?? ''));

            if (
                ($respSelloDoc->estado ?? '') !== 'RECHAZADO' &&
                !empty($correo) &&
                filter_var($correo, FILTER_VALIDATE_EMAIL)
            ) {
                try {
                    $this->procesarCorreoConArchivosAdjuntos(
                        $codigoGeneracion,
                        $factura[0]['jsonDTE'],
                        $datos,
                        ""
                    );

                    $modelSellos->update($factura[0]['idsellosDTE'], [
                        'correoEnviado'      => 1,
                        'fechaCorreoEnviado' => date('Y-m-d H:i:s'),
                        'errorCorreo'        => null,
                    ]);

                    $dataSelloProcesado['correoEnviado'] = true;

                } catch (\Throwable $e) {
                    log_message('error', 'Error reenviando correo DTE ' . $codigoGeneracion . ': ' . $e->getMessage());

                    $modelSellos->update($factura[0]['idsellosDTE'], [
                        'correoEnviado'      => 0,
                        'fechaCorreoEnviado' => null,
                        'errorCorreo'        => $e->getMessage(),
                    ]);

                    $dataSelloProcesado['correoEnviado'] = false;
                    $dataSelloProcesado['correoError'] = $e->getMessage();
                }
            } else {
                $modelSellos->update($factura[0]['idsellosDTE'], [
                    'correoEnviado'      => 0,
                    'fechaCorreoEnviado' => null,
                    'errorCorreo'        => 'Correo omitido: DTE rechazado, correo vacío o correo inválido.',
                ]);

                $dataSelloProcesado['correoEnviado'] = false;
                $dataSelloProcesado['correoOmitido'] = true;
            }

            $dataSelloProcesado['error'] = false;
            $dataSelloProcesado['message'] = "Se ha reenviado el DTE.";
            $dataSelloProcesado['detalleMSG'] = "Firmado y sellado con exito.";
            header('Content-Type: application/json; charset=utf-8');
            return json_encode($dataSelloProcesado);
        } catch (\Exception $e) {
            $db->transRollback();

            $dataSelloProcesado['error'] = true;
            $dataSelloProcesado['message'] = "Ha ocurrido un problema con el sellado.";
            $dataSelloProcesado['detalleMSG'] = $e->getMessage();
            header('Content-Type: application/json; charset=utf-8');
            return json_encode($dataSelloProcesado);
        }
    }

    public function firmarDTE($arregloDTE, $dataSeguridad)
    {

        $clientFirmaDoc = new CURLRequest(
            new \Config\App(),
            new \CodeIgniter\HTTP\URI()
        );

        $dataFirma = array(
            "nit" => $dataSeguridad['nit'],
            "passwordPri" => $dataSeguridad['passwordFirma'],
            "activo" => true,
            "dteJson" => $arregloDTE
        );

        $clientFirmaDoc->setHeader("Content-Type", "application/json");
        $clientFirmaDoc->setHeader("User-Agent", "MegaloadTest/01");

        var_dump($dataFirma);
        exit;

        //firmando el documento
        $responseFirmaDoc = $clientFirmaDoc->request('POST', $dataSeguridad['urlFirmador'], ['body' => json_encode($dataFirma)]);

        $responseDocFirmadoData = json_decode($responseFirmaDoc->getBody());

        return $responseDocFirmadoData->body;
    }

    public function actualizarFirma($jsonDTE)
    {


        $dataSeguridad = $this->modelSeguridad->find();

        $dataFirma = array(
            "nit" => $dataSeguridad[0]['nit'],
            "passwordPri" => $dataSeguridad[0]['passwordFirma'],
            "activo" => true,
            "dteJson" => json_decode($jsonDTE, true)
        );

        $clientFirmaDoc = new CURLRequest(
            new \Config\App(),
            new \CodeIgniter\HTTP\URI()
        );

        $clientFirmaDoc->setHeader("Content-Type", "application/json");
        $clientFirmaDoc->setHeader("User-Agent", "MegaloadTest/01");

        //firmando el documento
        $responseFirmaDoc = $clientFirmaDoc->request('POST', $dataSeguridad[0]['urlFirmador'], ['body' => json_encode($dataFirma)]);

        $responseDocFirmadoData = json_decode($responseFirmaDoc->getBody());

        return $responseDocFirmadoData->body;
    }

    public function validarToken($dataSeguridad)
    {

        $token = "";

        $fechaToken = explode(' ', $dataSeguridad['fechaTokenMH']);

        $dateTo = new Time($fechaToken[0]);
        $dateFrom = new Time('now');
        $interval = $dateTo->diff($dateFrom);
        $bearer = "";

        if ($interval->days >= (int)$dataSeguridad['vidaToken']) {

            /*inicia proceso de TOKEN */
            $clientLogin = new CURLRequest(
                new \Config\App(),
                new \CodeIgniter\HTTP\URI()
            );

            $clientLogin->setHeader("Content-Type", "application/x-www-form-urlencoded");
            $clientLogin->setHeader("User-Agent", "MegaloadTest/01");

            $dataLogin = array(
                'user' => $dataSeguridad['nit'],
                'pwd' => $dataSeguridad['tokenMH']
            );

            //Actualizando el token
            $responseLogin = $clientLogin->request('POST', $dataSeguridad['urlBearerToken'], ['form_params' => $dataLogin, 'verify' => true, 'http_errors' => false]);

            $responseLoginData = json_decode($responseLogin->getBody());

            $bearer = $responseLoginData->body;

            if ($responseLoginData->status == "OK") {

                $updateDataToken = [
                    'fechaTokenMH' => date('Y-m-d H:i:s'),
                    'usuarioSolicita' => session()->get('username'),
                    'respuestaTokenMH' => json_encode($responseLoginData),
                    'bearerTokenMH' => $bearer->token
                ];

                $actionSuccesfully = $this->modelSeguridad->update($dataSeguridad['codigoEmpresa'], $updateDataToken);
            }

            unset($clientLogin);
            /* finaliza proceso de TOKEN */
        }

        if (isset($bearer->token) === false && empty($bearer->token) === true) {
            $token = $dataSeguridad['bearerTokenMH'];
        } else {
            $token = $bearer->token;
        }

        return $token;
    }

    public function sellarDTE($dataSeguridad, $token, $idEnvio, string $codigoTipoDTE, $version, $codigoGeneracion, $documentoFirmado)
    {

        $clientSelladoDoc = new CURLRequest(
            new \Config\App(),
            new \CodeIgniter\HTTP\URI()
        );

        $clientSelladoDoc->setHeader("Content-Type", "application/json");
        $clientSelladoDoc->setHeader("User-Agent", "MegaloadTest/01");
        $clientSelladoDoc->setHeader("Authorization", $token);

        //para metros para sellar el documento
        $dataSelloParams = array(
            "ambiente" => $dataSeguridad['ambiente'],
            "idEnvio" => $idEnvio,
            "version" => $version,
            "tipoDte" => $codigoTipoDTE,
            "documento" => $documentoFirmado,
            "codigoGeneracion" => $codigoGeneracion
        );

        $responseSelladoDoc = $clientSelladoDoc->request('POST', $dataSeguridad['urlRecepcionDTE'], ['body' => json_encode($dataSelloParams), 'verify' => true, 'http_errors' => false]);

        $responseSelladoDocData = json_decode($responseSelladoDoc->getBody());

        return $responseSelladoDocData;
    }

    public function procesarCorreoConArchivosAdjuntos($codigoGeneracion, $jsonDTE, $datos, $condiciones = "")
    {

        // Guardar el archivo JSON

        $nombreArchivo = $codigoGeneracion . '.json';
        $rutaArchivo = WRITEPATH . 'uploads/' . $nombreArchivo;

        // Convertir el array a formato JSON
        $contenidoJSON = json_encode($jsonDTE, JSON_PRETTY_PRINT);

        // Guardar el archivo
        file_put_contents($rutaArchivo, $contenidoJSON);

        // Generar el PDF y guardarlo temporalmente en 'uploads'
        $rutaPDF = $this->generarPDF($codigoGeneracion, true, $condiciones);

        // Llamar al EmailController para enviar el archivo por correo
        $emailService = new EmailService();

        $mensajeCorreo = "Estimado (o) cliente " . $datos->receptor->nombre . " <p>Adjuntamos notificación de Factura Tributaria Electrónica de la transacción de fecha: <span style='font-weight: bold'>" . $datos->dte->fecEmi . "</span></p>";
        $mensajeCorreo .= "<p>En caso de que no reconozca la transacción, puede comunicarse al " . json_decode($jsonDTE)->emisor->telefono . "</p>";
        $mensajeCorreo .= "<p>Para validar la facturacion puede ingresar <a href='https://admin.factura.gob.sv/consultaPublica?ambiente=" . $datos->dte->ambiente . "&codGen=" . $datos->dte->codigoGeneracion . "&fechaEmi=" . $datos->dte->fecEmi . "' >aqui</a></p>";

        // Preparar adjuntos para el correo
        $adjuntos = [$rutaArchivo, $rutaPDF];

        $resultadoCorreo = $emailService->sendEmail($datos->receptor->correo, 'Notificación de Factura Electrónica', $mensajeCorreo, $adjuntos);

        // Imprimir o registrar el resultado del envío de correo
        log_message('info', $resultadoCorreo);

        /*// Mover el archivo después de enviar el correo
        $directorioBase = 'G:/My Drive/archivos_procesados/';

        $anio = date('Y');
        $mes = date('m');

        $directorioDestino = $directorioBase . $anio . '/' . $mes . '/';

        if (!is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0777, true); // Crea el directorio si no existe
        }
        // Nueva ruta para cada archivo en el directorio definitivo
        $nuevaRutaArchivo = $directorioDestino . basename($rutaArchivo);
        $nuevaRutaPDF = $directorioDestino . basename($rutaPDF);

        rename($rutaArchivo, $nuevaRutaArchivo);
        rename($rutaPDF, $nuevaRutaPDF);

        log_message('info', 'Archivos movidos a ' . $directorioDestino);*/
    }

    public function descargarJSON($codigoGeneracion)
    {
        $model = new SellosdteModel();
        $registro = $model->getJsonDTEByCodigoGeneracion($codigoGeneracion);

        if (!$registro || !$registro['jsonDTE']) {
            return $this->response->setStatusCode(404, 'Registro no encontrado o sin contenido JSON');
        }

        $jsonDTE = $registro['jsonDTE'];
        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $codigoGeneracion . '.json"')
            ->setBody($jsonDTE);
    }

    public function factura_correo_receptor($codigoGeneracion)
    {
        $model = new SellosdteModel();
        $correoData = $model->getCorreoReceptor($codigoGeneracion);
        return $this->response->setJSON($correoData);
    }

    public function factura_reenviar_correo($codigoGeneracion)
    {

        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        $model = new SellosdteModel();
        $factura = $model->getJsonDTEByCodigoGeneracion($codigoGeneracion);

        // Soportar retorno como array directo o como array[0]
        $row = null;
        if (is_array($factura) && isset($factura['jsonDTE'])) {
            $row = $factura;
        } elseif (is_array($factura) && isset($factura[0]) && isset($factura[0]['jsonDTE'])) {
            $row = $factura[0];
        }

        if (!$row || empty($row['jsonDTE'])) {
            return $this->response->setStatusCode(404)->setJSON([
                'error' => true,
                'message' => 'No se encontró el DTE o no tiene JSON asociado.',
            ]);
        }

        $jsonDTE = json_decode($row['jsonDTE']);
        if (json_last_error() !== JSON_ERROR_NONE || !$jsonDTE) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => true,
                'message' => 'El JSON del DTE está corrupto o no es válido.',
                'detalle' => json_last_error_msg(),
            ]);
        }

        // Validar campos mínimos
        $correo = $jsonDTE->receptor->correo ?? '';
        if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => true,
                'message' => 'El receptor no tiene un correo válido para reenviar.',
                'correo' => $correo,
            ]);
        }

        $datos = new \stdClass();
        $datos->receptor = (object)[
            'nombre' => ($jsonDTE->receptor->nombre ?? ''),
            'correo' => $correo
        ];
        $datos->dte = (object)[
            'fecEmi' => ($jsonDTE->identificacion->fecEmi ?? ''),
            'codigoGeneracion' => ($jsonDTE->identificacion->codigoGeneracion ?? $codigoGeneracion),
            'ambiente' => ($jsonDTE->identificacion->ambiente ?? '')
        ];


        try {
            $this->procesarCorreoConArchivosAdjuntos($codigoGeneracion, $row['jsonDTE'], $datos, "");

            // Marcar correo como enviado después del reenvío exitoso
            $model->where('codigoGeneracion', $codigoGeneracion)
                ->set([
                    'correoEnviado'      => 1,
                    'fechaCorreoEnviado' => date('Y-m-d H:i:s'),
                    'errorCorreo'        => null,
                ])
                ->update();

            return $this->response->setJSON([
                'error'            => false,
                'message'          => 'Correo reenviado correctamente.',
                'correo'           => $correo,
                'correoReceptor'   => $correo,
                'correoEnviado'    => true,
                'codigoGeneracion' => $codigoGeneracion
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error reenviando correo DTE: ' . $e->getMessage());

            // Marcar correo como no enviado y guardar error
            $model->where('codigoGeneracion', $codigoGeneracion)
                ->set([
                    'correoEnviado'      => 0,
                    'fechaCorreoEnviado' => null,
                    'errorCorreo'        => $e->getMessage(),
                ])
                ->update();



            return $this->response->setStatusCode(500)->setJSON([
                'error'            => true,
                'message'          => 'No se pudo reenviar el correo.',
                'detalle'          => $e->getMessage(),
                'correoEnviado'    => false,
                'codigoGeneracion' => $codigoGeneracion
            ]);
        }
    }

}
