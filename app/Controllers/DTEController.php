<?php

namespace App\Controllers;

use App\Models\SeguridadModel;
use App\Models\SellosdteModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\URI;

class DTEController extends ResourceController
{
    private $modelSeguridad;
    protected $format = 'json';
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->modelSeguridad = new SeguridadModel();

    }

    private function generarJSONSujetoExcluido($correlativo): array
    {
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');

        $detalles = $this->generarDetalleYResumenSujetoExcluido();

        return [
            "identificacion" => [
                "version" => 1,
                "ambiente" => "00",
                "tipoDte" => "14",
                "numeroControl" => "DTE-14-M001P001-" . str_pad($correlativo, 15, "0", STR_PAD_LEFT),
                "codigoGeneracion" => strtoupper($this->generarUUID()),
                "tipoModelo" => 1,
                "tipoOperacion" => 1,
                "tipoContingencia" => null,
                "motivoContin" => null,
                "fecEmi" => $fecha,
                "horEmi" => $hora,
                "tipoMoneda" => "USD"
            ],
            "emisor" => [
                "nit" => "06141101161042",
                "nrc" => "2473109",
                "nombre" => "megaload",
                "codActividad" => "49233",
                "descActividad" => "Transporte de carga internacional",
                "direccion" => [
                    "departamento" => "05",
                    "municipio" => "03",
                    "complemento" => "direccion"
                ],
                "telefono" => "1234-1234",
                "codEstableMH" => "M001",
                "codEstable" => "S001",
                "codPuntoVentaMH" => "P001",
                "codPuntoVenta" => "PV01",
                "correo" => "correo@corre.com"
            ],
            "sujetoExcluido" => [
                "tipoDocumento" => "13",
                "numDocumento" => "033872884",
                "nombre" => "Jose Luis Reyes",
                "codActividad" => null,
                "descActividad" => "16100",
                "direccion" => [
                    "departamento" => "05",
                    "municipio" => "22",
                    "complemento" => "Res. Los Laureles 1, Complejo Urbano Montemar"
                ],
                "telefono" => "79075851",
                "correo" => "joseluis.r85@gmail.com"
            ],
            "cuerpoDocumento" => $detalles['cuerpoDocumento'],
            "resumen" => $detalles['resumen'],
            "apendice" => null
        ];
    }

    private function generarJSONNotaCredito($correlativo): array
    {
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');

        $documentoRelacionado = (string)rand(7000, 8000);

        $detalles = $this->generarDetalleYResumenNotaCredito($documentoRelacionado);

        return [
            "identificacion" => [
                "version" => 3,
                "ambiente" => "00",
                "tipoDte" => "05",
                "numeroControl" => "DTE-05-M001P001-" . str_pad($correlativo, 15, "0", STR_PAD_LEFT),
                "codigoGeneracion" => strtoupper($this->generarUUID()),
                "tipoModelo" => 1,
                "tipoOperacion" => 1,
                "tipoContingencia" => null,
                "motivoContin" => null,
                "fecEmi" => $fecha,
                "horEmi" => $hora,
                "tipoMoneda" => "USD"
            ],
            "documentoRelacionado" => [[
                "tipoDocumento" => "03",
                "tipoGeneracion" => 1,
                "numeroDocumento" => $documentoRelacionado,
                "fechaEmision" => date('Y-m-d', strtotime('-1 day'))
            ]],
            "emisor" => [
                "nit" => "06141101161042",
                "nrc" => "2473109",
                "nombre" => "megaload",
                "codActividad" => "49233",
                "descActividad" => "Transporte de carga internacional",
                "nombreComercial" => null,
                "tipoEstablecimiento" => "02",
                "direccion" => [
                    "departamento" => "05",
                    "municipio" => "02",
                    "complemento" => "POLIG. E, CTON. FLOR AMARILLA, LOTE 626 C,"
                ],
                "telefono" => "25305293",
                "correo" => "info@grupomegaload.com"
            ],
            "receptor" => [
                "nit" => "14082711851010",
                "nrc" => "2885067",
                "nombre" => "Jose Luis Reyes",
                "codActividad" => "58200",
                "descActividad" => "Edición de programas informáticos (software)",
                "nombreComercial" => "jose reyes",
                "direccion" => [
                    "departamento" => "05",
                    "municipio" => "22",
                    "complemento" => "Res. Los Laureles 1, Complejo Urbano Montemar"
                ],
                "telefono" => "79075851",
                "correo" => "joseluis.r85@gmail.com"
            ],
            "ventaTercero" => null,
            "cuerpoDocumento" => $detalles['cuerpoDocumento'],
            "resumen" => $detalles['resumen'],
            "extension" => [
                "nombEntrega" => null,
                "docuEntrega" => null,
                "nombRecibe" => null,
                "docuRecibe" => null,
                "observaciones" => null
            ],
            "apendice" => null
        ];
    }

    private function generarJSONNotaDebito($correlativo): array
    {
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');

        $documentoRelacionado = (string)rand(7000, 8000);

        $detalles = $this->generarDetalleYResumenNotaDebito($documentoRelacionado);

        return [
            "identificacion" => [
                "version" => 3,
                "ambiente" => "00",
                "tipoDte" => "06",
                "numeroControl" => "DTE-06-M001P001-" . str_pad($correlativo, 15, "0", STR_PAD_LEFT),
                "codigoGeneracion" => strtoupper($this->generarUUID()),
                "tipoModelo" => 1,
                "tipoOperacion" => 1,
                "tipoContingencia" => null,
                "motivoContin" => null,
                "fecEmi" => $fecha,
                "horEmi" => $hora,
                "tipoMoneda" => "USD"
            ],
            "documentoRelacionado" => [[
                "tipoDocumento" => "03",
                "tipoGeneracion" => 1,
                "numeroDocumento" => $documentoRelacionado,
                "fechaEmision" => date('Y-m-d', strtotime('-1 day'))
            ]],
            "emisor" => [
                "nit" => "06141101161042",
                "nrc" => "2473109",
                "nombre" => "megaload",
                "codActividad" => "49233",
                "descActividad" => "Transporte de carga internacional",
                "nombreComercial" => null,
                "tipoEstablecimiento" => "02",
                "direccion" => [
                    "departamento" => "05",
                    "municipio" => "02",
                    "complemento" => "POLIG. E, CTON. FLOR AMARILLA, LOTE 626 C,"
                ],
                "telefono" => "25305293",
                "correo" => "info@grupomegaload.com"
            ],
            "receptor" => [
                "nit" => "14082711851010",
                "nrc" => "2885067",
                "nombre" => "Jose Luis Reyes",
                "codActividad" => "58200",
                "descActividad" => "Edición de programas informáticos (software)",
                "nombreComercial" => "jose reyes",
                "direccion" => [
                    "departamento" => "05",
                    "municipio" => "22",
                    "complemento" => "Res. Los Laureles 1, Complejo Urbano Montemar"
                ],
                "telefono" => "79075851",
                "correo" => "joseluis.r85@gmail.com"
            ],
            "ventaTercero" => null,
            "cuerpoDocumento" => $detalles['cuerpoDocumento'],
            "resumen" => $detalles['resumen'],
            "extension" => [
                "nombEntrega" => null,
                "docuEntrega" => null,
                "nombRecibe" => null,
                "docuRecibe" => null,
                "observaciones" => null
            ],
            "apendice" => null
        ];
    }

    private function generarDetalleYResumenSujetoExcluido(): array
    {
        $numItems = rand(1, 2);
        $items = [];
        $total = 0;

        for ($i = 1; $i <= $numItems; $i++) {
            $precio = rand(10, 60);
            $cantidad = rand(1, 3);
            $compra = round($precio * $cantidad, 2);

            $items[] = [
                "numItem" => $i,
                "tipoItem" => 2,
                "cantidad" => $cantidad,
                "codigo" => null,
                "uniMedida" => 59,
                "descripcion" => "Servicio Excluido #$i",
                "precioUni" => $precio,
                "montoDescu" => 0,
                "compra" => $compra
            ];

            $total += $compra;
        }

        $resumen = [
            "totalCompra" => $total,
            "descu" => 0,
            "totalDescu" => 0,
            "subTotal" => $total,
            "ivaRete1" => 0,
            "reteRenta" => 0,
            "totalPagar" => $total,
            "totalLetras" => $this->convertirALetras($total),
            "condicionOperacion" => 1,
            "pagos" => [
                [
                    "codigo" => "01",
                    "montoPago" => $total,
                    "plazo" => null,
                    "referencia" => "",
                    "periodo" => null
                ]
            ],
            "observaciones" => null
        ];

        return [
            'cuerpoDocumento' => $items,
            'resumen' => $resumen
        ];
    }

    private function generarDetalleYResumenNotaCredito($documentoRelacionado): array
    {
        $numItems = rand(1, 2);
        $items = [];
        $total = 0;

        for ($i = 1; $i <= $numItems; $i++) {
            $precio = rand(10, 60);
            $cantidad = rand(1, 3);
            $compra = round($precio * $cantidad, 2);

            $items[] = [
                "numItem" => $i,
                "tipoItem" => 2,
                "numeroDocumento" => $documentoRelacionado,
                "codigo" => null,
                "codTributo" => null,
                "descripcion" => "Servicio manto software #$i",
                "cantidad" => $cantidad,
                "uniMedida" => 59,
                "precioUni" => (float)number_format($precio, 2),
                "montoDescu" => 0,
                "ventaNoSuj" => 0,
                "ventaExenta" => 0,
                "ventaGravada" => $compra,
                "tributos" => [
                    "20"
                ]
            ];

            $total += $compra;
        }

        $totalIva = $total * 0.13;

        $resumen = [
            "totalNoSuj" => 0,
            "totalExenta" => 0,
            "totalGravada" => (float)number_format($total, 2),
            "subTotalVentas" => (float)number_format($total, 2),
            "descuNoSuj" => 0,
            "descuExenta" => 0,
            "descuGravada" => 0,
            "totalDescu" => 0,
            "tributos" => [
                [
                    "codigo" => "20",
                    "descripcion" => "Impuesto al Valor Agregado 13%",
                    "valor" => (float)number_format($totalIva, 2)
                ]
            ],
            "subTotal" => (float)number_format($total, 2),
            "ivaPerci1" => 0,
            "ivaRete1" => 0,
            "montoTotalOperacion" => (float)number_format($total + $totalIva, 2),
            "totalLetras" => $this->convertirALetras($total + $totalIva),
            "condicionOperacion" => 1,
            "reteRenta" => 0
        ];

        return [
            'cuerpoDocumento' => $items,
            'resumen' => $resumen
        ];
    }

    private function generarDetalleYResumenNotaDebito($documentoRelacionado): array
    {
        $numItems = rand(1, 2);
        $items = [];
        $total = 0;

        for ($i = 1; $i <= $numItems; $i++) {
            $precio = rand(10, 60);
            $cantidad = rand(1, 3);
            $compra = round($precio * $cantidad, 2);

            $items[] = [
                "numItem" => $i,
                "tipoItem" => 2,
                "numeroDocumento" => $documentoRelacionado,
                "cantidad" => $cantidad,
                "codigo" => null,
                "codTributo" => null,
                "descripcion" => "Servicio manto software #$i",
                "uniMedida" => 59,
                "precioUni" => (float)number_format($precio, 2),
                "montoDescu" => 0,
                "ventaNoSuj" => 0,
                "ventaExenta" => 0,
                "ventaGravada" => $compra,
                "tributos" => [
                    "20"
                ]
            ];

            $total += $compra;
        }

        $totalIva = $total * 0.13;

        $resumen = [
            "totalNoSuj" => 0,
            "totalExenta" => 0,
            "totalGravada" => (float)number_format($total, 2),
            "subTotalVentas" => (float)number_format($total, 2),
            "descuNoSuj" => 0,
            "descuExenta" => 0,
            "descuGravada" => 0,
            "totalDescu" => 0,
            "tributos" => [
                [
                    "codigo" => "20",
                    "descripcion" => "Impuesto al Valor Agregado 13%",
                    "valor" => (float)number_format($totalIva, 2)
                ]
            ],
            "subTotal" => (float)number_format($total, 2),
            "ivaPerci1" => 0,
            "ivaRete1" => 0,
            "reteRenta" => 0,
            "montoTotalOperacion" => (float)number_format($total + $totalIva, 2),
            "totalLetras" => $this->convertirALetras($total + $totalIva),
            "condicionOperacion" => 1,
            "numPagoElectronico" => null
        ];

        return [
            'cuerpoDocumento' => $items,
            'resumen' => $resumen
        ];
    }

    public function generarSujetoExcluido()
    {
        $inicio = (int)$this->request->getGet('inicio') ?? 1;
        $total = (int)$this->request->getGet('total') ?? 1;

        $dtes = [];

        for ($i = 0; $i < $total; $i++) {
            $correlativo = $inicio + $i;
            $dte = $this->generarJSONSujetoExcluido($correlativo);
            $firma = $this->firmarDTE($dte);
            $fechaFirma = date('Y-m-d H:i:s');

            $token = $this->obtenerTokenMH(); // O tu token fijo si lo estás usando aún
            $sello = $this->enviarASelloMH(
                $firma['body'],
                $dte['identificacion']['numeroControl'],
                $dte['identificacion']['codigoGeneracion'],
                $token
            );

            $this->guardarEnBD([
                'identificador' => $correlativo,
                'codigoGeneracion' => $dte['identificacion']['codigoGeneracion'],
                'correlativoCRM' => $correlativo,
                'numeroControl' => $dte['identificacion']['numeroControl'],
                'codigoTipoDTE' => '14',
                'version' => '1',
                'fechaFactura' => date('Y-m-d H:i:s'),
                'jsonDTE' => json_encode($dte),
                'firmaFactura' => $firma['body'],
                'fechaFirma' => $fechaFirma,
                'usuarioFirma' => 'automatico',
                'usuarioSello' => 'automatico',
                'fechaSello' => $this->formatearFechaSello($sello['fhProcesamiento'] ?? null),
                'errorMH' => json_encode($sello),
                'codigoPuntoVenta' => 'PV01',
                'idEstadoDTE' => 1
            ]);

            $dtes[] = [
                'dte' => $dte,
                'firma' => $firma,
                'sello' => $sello
            ];
        }

        return $this->respond($dtes);
    }

    public function generarNotaCredito()
    {
        $inicio = (int)$this->request->getGet('inicio') ?? 1;
        $total = (int)$this->request->getGet('total') ?? 1;

        $dtes = [];

        for ($i = 0; $i < $total; $i++) {
            $correlativo = $inicio + $i;
            $dte = $this->generarJSONNotaCredito($correlativo);

            $dataSeguridad = $this->modelSeguridad->find();

            $firma = $this->firmarDTE($dte, $dataSeguridad);
            $fechaFirma = date('Y-m-d H:i:s');

            $token = $this->validarToken($dataSeguridad);
            $sello = $this->enviarASelloMH(
                $firma['body'],
                $dte['identificacion']['numeroControl'],
                $dte['identificacion']['codigoGeneracion'],
                $token,
                $dte['identificacion']['tipoDte']
            );

            $this->guardarEnBD([
                'identificador' => $correlativo,
                'codigoGeneracion' => $dte['identificacion']['codigoGeneracion'],
                'correlativoCRM' => $correlativo,
                'numeroControl' => $dte['identificacion']['numeroControl'],
                'codigoTipoDTE' => $dte['identificacion']['tipoDte'],
                'version' => $dte['identificacion']['version'],
                'fechaFactura' => date('Y-m-d H:i:s'),
                'jsonDTE' => json_encode($dte),
                'firmaFactura' => $firma['body'],
                'fechaFirma' => $fechaFirma,
                'usuarioFirma' => 'automatico',
                'usuarioSello' => 'automatico',
                'fechaSello' => $this->formatearFechaSello($sello['fhProcesamiento'] ?? null),
                'errorMH' => json_encode($sello),
                'codigoPuntoVenta' => 'PV01',
                'idEstadoDTE' => 1
            ]);

            $dtes[] = [
                'dte' => $dte,
                'firma' => $firma,
                'sello' => $sello
            ];
        }

        return $this->respond($dtes);
    }

    public function generarNotaDebito()
    {
        $inicio = (int)$this->request->getGet('inicio') ?? 1;
        $total = (int)$this->request->getGet('total') ?? 1;

        $dtes = [];

        for ($i = 0; $i < $total; $i++) {
            $correlativo = $inicio + $i;
            $dte = $this->generarJSONNotaDebito($correlativo);

            $dataSeguridad = $this->modelSeguridad->find();

            $firma = $this->firmarDTE($dte, $dataSeguridad);
            $fechaFirma = date('Y-m-d H:i:s');

            $token = $this->validarToken($dataSeguridad);
            $sello = $this->enviarASelloMH(
                $firma['body'],
                $dte['identificacion']['numeroControl'],
                $dte['identificacion']['codigoGeneracion'],
                $token,
                $dte['identificacion']['tipoDte']
            );

            $this->guardarEnBD([
                'identificador' => $correlativo,
                'codigoGeneracion' => $dte['identificacion']['codigoGeneracion'],
                'correlativoCRM' => $correlativo,
                'numeroControl' => $dte['identificacion']['numeroControl'],
                'codigoTipoDTE' => $dte['identificacion']['tipoDte'],
                'version' => $dte['identificacion']['version'],
                'fechaFactura' => date('Y-m-d H:i:s'),
                'jsonDTE' => json_encode($dte),
                'firmaFactura' => $firma['body'],
                'fechaFirma' => $fechaFirma,
                'usuarioFirma' => 'automatico',
                'usuarioSello' => 'automatico',
                'fechaSello' => $this->formatearFechaSello($sello['fhProcesamiento'] ?? null),
                'errorMH' => json_encode($sello),
                'codigoPuntoVenta' => 'PV01',
                'idEstadoDTE' => 1
            ]);

            $dtes[] = [
                'dte' => $dte,
                'firma' => $firma,
                'sello' => $sello
            ];
        }

        return $this->respond($dtes);
    }

    private function generarJSONExportacion($correlativo)
    {

        $detalles = $this->generarDetalleYResumenExportacion();

        return [
            "identificacion" => [
                "version" => 1,
                "ambiente" => "00",
                "tipoDte" => "11",
                "numeroControl" => "DTE-11-M001P001-" . str_pad($correlativo, 15, "0", STR_PAD_LEFT),
                "codigoGeneracion" => strtoupper($this->generarUUID()),
                "tipoModelo" => 1,
                "tipoOperacion" => 1,
                "tipoContingencia" => null,
                "motivoContigencia" => null,
                "fecEmi" => date('Y-m-d'),
                "horEmi" => date('H:i:s'),
                "tipoMoneda" => "USD"
            ],
            "emisor" => [
                "nit" => "06141101161042",
                "nrc" => "2473109",
                "nombre" => "megaload",
                "codActividad" => "49233",
                "descActividad" => "Transporte de carga internacional",
                "nombreComercial" => "megaload",
                "tipoEstablecimiento" => "02",
                "direccion" => [
                    "departamento" => "05",
                    "municipio" => "03",
                    "complemento" => "direccion"
                ],
                "telefono" => "1234-1234",
                "correo" => "correo@corre.com",
                "codEstableMH" => "M001",
                "codEstable" => "S001",
                "codPuntoVentaMH" => "P001",
                "codPuntoVenta" => "PV01",
                "regimen" => "EX-1.1000.000",
                "recintoFiscal" => "21",
                "tipoItemExpor" => 1
            ],
            "receptor" => [
                "tipoDocumento" => "36",
                "numDocumento" => "14082711851010",
                "nombre" => "Cliente Exterior",
                "descActividad" => "Servicios internacionales",
                "codPais" => "9901",
                "nombrePais" => "PAÍS EXTRANJERO",
                "complemento" => "Zona exportación, dirección exterior",
                "nombreComercial" => "Comercial Exterior",
                "tipoPersona" => 1,
                "telefono" => "55555555",
                "correo" => "cliente@exterior.com"
            ],
            "otrosDocumentos" => null,
            "ventaTercero" => null,
            "cuerpoDocumento" => $detalles['cuerpoDocumento'],
            "resumen" => $detalles['resumen'],
            "apendice" => null
        ];
    }

    public function generarExportacion()
    {
        $inicio = (int)$this->request->getGet('inicio') ?? 1;
        $total = (int)$this->request->getGet('total') ?? 1;

        $dtes = [];

        for ($i = 0; $i < $total; $i++) {
            $correlativo = $inicio + $i;
            $dte = $this->generarJSONExportacion($correlativo);
            $firma = $this->firmarDTE($dte);
            $fechaFirma = date('Y-m-d H:i:s');

            $token = $this->obtenerTokenMH();
            $sello = $this->enviarASelloMH($firma['body'], $dte['identificacion']['numeroControl'], $dte['identificacion']['codigoGeneracion'], $token);

            $this->guardarEnBD([
                'identificador' => $correlativo,
                'codigoGeneracion' => $dte['identificacion']['codigoGeneracion'],
                'correlativoCRM' => $correlativo,
                'numeroControl' => $dte['identificacion']['numeroControl'],
                'codigoTipoDTE' => '11',
                'version' => '1',
                'fechaFactura' => date('Y-m-d H:i:s'),
                'jsonDTE' => json_encode($dte),
                'firmaFactura' => $firma['body'],
                'fechaFirma' => $fechaFirma,
                'usuarioFirma' => 'automatico',
                'usuarioSello' => 'automatico',
                'fechaSello' => $this->formatearFechaSello($sello['fhProcesamiento'] ?? null),
                'errorMH' => json_encode($sello),
                'codigoPuntoVenta' => 'PV01',
                'idEstadoDTE' => 1
            ]);

            $dtes[] = [
                'dte' => $dte,
                'firma' => $firma,
                'sello' => $sello
            ];
        }

        return $this->respond($dtes);
    }

    public function generarCreditoFiscal()
    {
        $dataSeguridad = $this->modelSeguridad->find();

        $inicio = (int)$this->request->getGet('inicio') ?? 1;
        $total = (int)$this->request->getGet('total') ?? 1;

        $dtes = [];

        for ($i = 0; $i < $total; $i++) {
            $correlativo = $inicio + $i;
            $dte = $this->generarJSONCreditoFiscal($correlativo);
            $firma = $this->firmarDTE($dte, $dataSeguridad);
            $fechaFirma = date('Y-m-d H:i:s');

            $token = $this->obtenerTokenMH();

            $sello = $this->enviarASelloMH($firma['body'], $dte['identificacion']['numeroControl'], $dte['identificacion']['codigoGeneracion'], $token, $dte['identificacion']['tipoDte']);

            $this->guardarEnBD([
                'identificador' => $correlativo,
                'codigoGeneracion' => $dte['identificacion']['codigoGeneracion'],
                'correlativoCRM' => $correlativo,
                'numeroControl' => $dte['identificacion']['numeroControl'],
                'codigoTipoDTE' => '03',
                'version' => '1',
                'fechaFactura' => date('Y-m-d H:i:s'),
                'jsonDTE' => json_encode($dte),
                'firmaFactura' => $firma['body'],
                'fechaFirma' => $fechaFirma,
                'usuarioFirma' => 'automatico',
                'usuarioSello' => 'automatico',
                'fechaSello' => $this->formatearFechaSello($sello['fhProcesamiento'] ?? null),
                'errorMH' => json_encode($sello),
                'codigoPuntoVenta' => 'PV01',
                'idEstadoDTE' => 1
            ]);

            $dtes[] = [
                'dte' => $dte,
                'firma' => $firma,
                'sello' => $sello
            ];
        }

        return $this->respond($dtes);
    }

    public function generarConsumidorFinal()
    {

        $inicio = (int)$this->request->getGet('inicio') ?? 1;
        $total = (int)$this->request->getGet('total') ?? 1;

        $dtes = [];

        for ($i = 0; $i < $total; $i++) {
            $correlativo = $inicio + $i;
            $dte = $this->generarJSONDTE($correlativo);
            $firma = $this->firmarDTE($dte);
            $fechaFirma = date('Y-m-d H:i:s');

            $token = $this->obtenerTokenMH();
            $sello = $this->enviarASelloMH($firma['body'], $dte['identificacion']['numeroControl'], $dte['identificacion']['codigoGeneracion'], $token);

            $this->guardarEnBD([
                'identificador' => $correlativo,
                'codigoGeneracion' => $dte['identificacion']['codigoGeneracion'],
                'correlativoCRM' => $correlativo,
                'numeroControl' => $dte['identificacion']['numeroControl'],
                'codigoTipoDTE' => '01',
                'version' => '1',
                'fechaFactura' => date('Y-m-d H:i:s'),
                'jsonDTE' => json_encode($dte),
                'firmaFactura' => $firma['body'],
                'fechaFirma' => $fechaFirma,
                'usuarioFirma' => 'automatico',
                'usuarioSello' => 'automatico',
                'fechaSello' => $this->formatearFechaSello($sello['fhProcesamiento'] ?? null),
                'errorMH' => json_encode($sello),
                'codigoPuntoVenta' => 'PV01',
                'idEstadoDTE' => 1
            ]);

            $dtes[] = [
                'dte' => $dte,
                'firma' => $firma,
                'sello' => $sello
            ];
        }

        return $this->respond($dtes);
    }

    private function generarJSONCreditoFiscal($correlativo)
    {

        $detalles = $this->generarDetalleYResumenCF();

        return [
            "identificacion" => [
                "version" => 3,
                "ambiente" => "00",
                "tipoDte" => "03",
                "numeroControl" => "DTE-03-M001P001-" . str_pad($correlativo, 15, "0", STR_PAD_LEFT),
                "codigoGeneracion" => strtoupper($this->generarUUID()),
                "tipoModelo" => 1,
                "tipoOperacion" => 1,
                "tipoContingencia" => null,
                "motivoContin" => null,
                "fecEmi" => date('Y-m-d'),
                "horEmi" => date('H:i:s'),
                "tipoMoneda" => "USD"
            ],
            "emisor" => [
                "nit" => "06141101161042",
                "nrc" => "2473109",
                "nombre" => "megaload",
                "nombreComercial" => "megaload",
                "telefono" => "1234-1234",
                "correo" => "correo@corre.com",
                "codActividad" => "49233",
                "descActividad" => "Transporte de carga internacional",
                "direccion" => [
                    "departamento" => "05",
                    "municipio" => "03",
                    "complemento" => "direccion"
                ],
                "codEstableMH" => "M001",
                "codEstable" => "S001",
                "codPuntoVentaMH" => "P001",
                "codPuntoVenta" => "PV01",
                "tipoEstablecimiento" => "02"
            ],
            "receptor" => [
                "nit" => "14082711851010",
                "nrc" => "2885067",
                "nombre" => "Cliente Demo",
                "nombreComercial" => "Jose Luis Reyes Ortiz",
                "codActividad" => "62010",
                "descActividad" => "Servicios de programación",
                "direccion" => [
                    "departamento" => "06",
                    "municipio" => "02",
                    "complemento" => "calle principal #123"
                ],
                "telefono" => "5555-5555",
                "correo" => "cliente@demo.com"
            ],
            "cuerpoDocumento" => $detalles['cuerpoDocumento'],
            "resumen" => $detalles['resumen'],
            "extension" => [
                "nombEntrega" => "Herbert Abrego",
                "docuEntrega" => "02026821-2",
                "nombRecibe" => "Jose Luis Reyes",
                "docuRecibe" => "03387288-4",
                "observaciones" => null,
                "placaVehiculo" => null
            ],
            "ventaTercero" => null,
            "otrosDocumentos" => null,
            "apendice" => null,
            "documentoRelacionado" => null
        ];
    }

    private function obtenerTokenMH()
    {
        return 'Bearer eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiIwNjE0MTEwMTE2MTA0MiIsImF1dGhvcml0aWVzIjpbIlVTRVIiLCJVU0VSX0FQSSIsIlVzdWFyaW8iXSwiY19uaXQiOiIwNjE0MTEwMTE2MTA0MiIsImNfZHVpIjoiMDYxNDExMDExNjEwNDIiLCJpYXQiOjE3NDQxMzcyMjgsImV4cCI6MTc0NDIyMzYyOH0.DatLLlEvWhwoLcl56gsR6bupvv2oUX7dS7LCehHMqPExwLW_TZpqZbYCz1bnQ2lDBY1v48RKl39btveLsO38-Q';
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
            $responseLogin = $clientLogin->request('POST', $dataSeguridad['urlBearerToken'], ['form_params' => $dataLogin, 'verify' => false, 'http_errors' => false]);

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


        }

        if (isset($bearer->token) === false && empty($bearer->token) === true) {
            $token = $dataSeguridad['bearerTokenMH'];
        } else {
            $token = $bearer->token;
        }

        return $token;
    }

    private function enviarASelloMH($firma, $numeroControl, $codigoGeneracion, $token, $tipoDocu)
    {
        $idEnvio = $this->extraerCorrelativoDeNumeroControl($numeroControl);
        $version = 3;
        $codigoTipoDTE = $tipoDocu;
        $ambiente = '00';
        $urlRecepcionDTE = 'https://apitest.dtes.mh.gob.sv/fesv/recepciondte';

        // Cliente cURL desde el service container (forma recomendada)
        $client = \Config\Services::curlrequest();

        $payload = [
            "ambiente" => $ambiente,
            "idEnvio" => $idEnvio,
            "version" => $version,
            "tipoDte" => $codigoTipoDTE,
            "documento" => $firma,
            "codigoGeneracion" => $codigoGeneracion
        ];

        $response = $client->post($urlRecepcionDTE, [
            'headers' => [
                'Authorization' => $token,
                'Content-Type' => 'application/json',
                'User-Agent' => 'MegaloadTest/01'
            ],
            'body' => json_encode($payload),
            'verify' => false,
            'http_errors' => false,
            'timeout' => 10 // Tiempo máximo de espera de 10 segundos
        ]);

        return json_decode($response->getBody(), true);
    }

    private function enviarASelloMHInvalidacion($firma, $numeroControl, $token, $seguridad)
    {

        $idEnvio = $this->extraerCorrelativoDeNumeroControl($numeroControl);
        $version = 2;
        $ambiente = $seguridad['ambiente'];
        if ($ambiente === '00') {
            $urlRecepcionDTE = 'https://apitest.dtes.mh.gob.sv/fesv/anulardte';
        } else {
            $urlRecepcionDTE = 'https://api.dtes.mh.gob.sv/fesv/anulardte';
        }

        $client = \Config\Services::curlrequest();

        $payload = [
            "ambiente" => $ambiente,
            "idEnvio" => $idEnvio,
            "version" => $version,
            "documento" => $firma,
        ];

        $response = $client->post($urlRecepcionDTE, [
            'headers' => [
                'Authorization' => $token,
                'Content-Type' => 'application/json',
                'User-Agent' => 'MegaloadTest/01'
            ],
            'body' => json_encode($payload),
            'verify' => false,
            'http_errors' => false
        ]);

        return $response;

    }

    private function guardarEnBD(array $data)
    {
        $builder = $this->db->table('sellosdte');
        $builder->insert([
            'identicadorNumInterno' => $data['identificador'],
            'codigoGeneracion' => $data['codigoGeneracion'],
            'correlativoFactCRM' => $data['correlativoCRM'],
            'numeroControlMH' => $data['numeroControl'],
            'codigoTipoDTE' => $data['codigoTipoDTE'],
            'version' => $data['version'],
            'fechaFactura' => $data['fechaFactura'],
            'jsonDTE' => $data['jsonDTE'],
            'firmaFactura' => $data['firmaFactura'],
            'fechaFirma' => $data['fechaFirma'],
            'usuarioFirma' => $data['usuarioFirma'],
            'usuarioSello' => $data['usuarioSello'],
            'fechaSello' => $data['fechaSello'],
            'errorMH' => $data['errorMH'],
            'codigoPuntoVenta' => $data['codigoPuntoVenta'],
            'idEstadoDTE' => $data['idEstadoDTE']
        ]);
    }

    private function actualizarAnulacionEnBD($data, $firma, $dteInfo, $invalidacion)
    {
        $builder = $this->db->table('sellosdte');
        $builder->where('idsellosDTE', $dteInfo['idsellosDTE']);
        $builder->where('codigoGeneracion', $dteInfo['codigoGeneracion']);

        $dataBeaty = $data;

        $fechaOriginal = $dataBeaty['fhProcesamiento'] ?? null;
        $fechaFormateada = null;

        if ($fechaOriginal) {
            $fechaObj = \DateTime::createFromFormat('d/m/Y H:i:s', $fechaOriginal);
            if ($fechaObj) {
                $fechaFormateada = $fechaObj->format('Y-m-d H:i:s');
            }
        }

        $builder->update([
            'jsonAnulacion' => json_encode($data),
            'firmaAnulacion' => $firma['body'],
            'selloAnulacion' => $dataBeaty['selloRecibido'] ?? null,
            'fechaAnulacion' => $fechaFormateada,
            'usuarioAnulacion' => $dteInfo['nombreEmisor'] ?? 'automatico',
            'solicitanteAnulacion' => $invalidacion['motivo']['nombreSolicita'] ?? null,
            'anulacionMotivo' => $invalidacion['motivo']['motivoAnulacion'] ?? null,
            'anulacionTipoDoc' => $invalidacion['documento']['tipoDte'] ?? null,
            'anulacionNumDoc' => $dteInfo['numeroControl'] ?? null,
            'anulacionTelefono' => $dteInfo['telefonoReceptor'] ?? null,
            'anulacionEmail' => $dteInfo['correoReceptor'] ?? null,
            'anulacionTipo' => $invalidacion['motivo']['tipoAnulacion'] ?? null,
            'idEstadoDTE' => 4
        ]);
    }


    private function formatearFechaSello($fecha)
    {
        if (!$fecha) return null;
        $dt = \DateTime::createFromFormat('d/m/Y H:i:s', $fecha);
        return $dt ? $dt->format('Y-m-d H:i:s') : null;
    }

    private function generarJSONDTE(int $correlativo): array
    {
        $detalles = $this->generarDetalleYResumen(1); // máximo 2 ítems
        return [
            "identificacion" => [
                "version" => 1,
                "ambiente" => "00",
                "tipoDte" => "01",
                "numeroControl" => "DTE-01-M001P001-" . str_pad($correlativo, 15, "0", STR_PAD_LEFT),
                "codigoGeneracion" => strtoupper($this->generarUUID()),
                "tipoModelo" => 1,
                "tipoOperacion" => 1,
                "tipoContingencia" => null,
                "motivoContin" => null,
                "fecEmi" => date('Y-m-d'),
                "horEmi" => date('H:i:s'),
                "tipoMoneda" => "USD"
            ],
            "documentoRelacionado" => null,
            "emisor" => [
                "nit" => "06141101161042",
                "nrc" => "2473109",
                "correo" => "correo@corre.com",
                "nombre" => "megaload",
                "telefono" => "1234-1234",
                "direccion" => [
                    "municipio" => "03",
                    "complemento" => "direccion",
                    "departamento" => "05"
                ],
                "codEstable" => "S001",
                "codActividad" => "49233",
                "codEstableMH" => "M001",
                "codPuntoVenta" => "PV01",
                "descActividad" => "Transporte de carga internacional",
                "codPuntoVentaMH" => "P001",
                "nombreComercial" => "megaload",
                "tipoEstablecimiento" => "02"
            ],
            "receptor" => [
                "tipoDocumento" => "13",
                "numDocumento" => "03387288-4",
                "nrc" => null,
                "nombre" => "Jose Luis Reyes Ortiz",
                "codActividad" => null,
                "descActividad" => null,
                "direccion" => [
                    "departamento" => "05",
                    "municipio" => "22",
                    "complemento" => "Res. Los Laureles 1, Complejo Urbano Montemar, Senda 3\nPol k, Casa 38"
                ],
                "telefono" => "79075851",
                "correo" => "joseluis.r85@gmail.com"
            ],
            "otrosDocumentos" => null,
            "ventaTercero" => null,
            "cuerpoDocumento" => $detalles['cuerpoDocumento'],
            "resumen" => $detalles['resumen'],
            "extension" => [
                "nombEntrega" => "Herbert Abrego",
                "docuEntrega" => "02026821-2",
                "nombRecibe" => "Jose Luis Reyes",
                "docuRecibe" => "03387288-4",
                "observaciones" => null,
                "placaVehiculo" => null
            ],
            "apendice" => null
        ];
    }

    private function generarDetalleYResumen(int $cantidadItems = 1): array
    {
        $cuerpo = [];
        $totalGravada = 0;
        $totalIva = 0;

        /*for ($i = 1; $i <= $cantidadItems; $i++) {
            $precio = rand(10, 500);
            $cantidad = rand(1, 5);
            $venta = round($precio * $cantidad, 2);
            $iva = round($venta * 0.115, 2);



            $cuerpo[] = $item;
            $totalGravada += $venta;
            $totalIva += $iva;
        }*/

        $item = [
            "numItem" => 1,
            "tipoItem" => 2,
            "numeroDocumento" => null,
            "cantidad" => 1,
            "codigo" => null,
            "codTributo" => null,
            "uniMedida" => 59,
            "descripcion" => "Piezas",
            "precioUni" => 100,
            "montoDescu" => 0,
            "ventaNoSuj" => 0,
            "ventaExenta" => 0,
            "ventaGravada" => 100,
            "tributos" => null,
            "psv" => 0,
            "noGravado" => 0,
            "ivaItem" => 11.5
        ];

        $cuerpo[] = $item;

        //$totalGravada = round($totalGravada, 2);
        //$totalIva = round($totalIva, 2);
        //$totalPagar = round($totalGravada + $totalIva, 2);

        $resumen = [
            "totalNoSuj" => 0,
            "totalExenta" => 0,
            "totalGravada" => 100,
            "subTotalVentas" => 100,
            "descuNoSuj" => 0,
            "descuExenta" => 0,
            "descuGravada" => 0,
            "porcentajeDescuento" => 0,
            "totalDescu" => 0,
            "tributos" => [],
            "subTotal" => 100,
            "ivaRete1" => 0,
            "reteRenta" => 0,
            "montoTotalOperacion" => 100,
            "totalNoGravado" => 0,
            "totalPagar" => 100,
            "totalLetras" => "CIEN DÓLARES ",
            "totalIva" => 11.5,
            "saldoFavor" => 0,
            "condicionOperacion" => 1,
            "pagos" => [
                [
                    "codigo" => "01",
                    "montoPago" => 100,
                    "plazo" => null,
                    "referencia" => "",
                    "periodo" => null
                ]
            ],
            "numPagoElectronico" => null
        ];

        return [
            'cuerpoDocumento' => $cuerpo,
            'resumen' => $resumen
        ];
    }

    private function generarDetalleYResumenCF(): array
    {
        // Generar 1 o 2 ítems
        $numItems = rand(1, 2);
        $items = [];
        $totalGravada = 0;

        for ($i = 1; $i <= $numItems; $i++) {
            $precio = rand(50, 500); // Precio unitario aleatorio
            $cantidad = rand(1, 3);
            $venta = round($precio * $cantidad, 2);

            $items[] = [
                "numItem" => $i,
                "tipoItem" => 2,
                "numeroDocumento" => null,
                "cantidad" => $cantidad,
                "codigo" => null,
                "codTributo" => null,
                "uniMedida" => 59,
                "descripcion" => "Producto/Servicio #" . $i,
                "precioUni" => $precio,
                "montoDescu" => 0,
                "ventaNoSuj" => 0,
                "ventaExenta" => 0,
                "ventaGravada" => $venta,
                "tributos" => ["20"],
                "psv" => 0,
                "noGravado" => 0
            ];

            $totalGravada += $venta;
        }

        $iva = round($totalGravada * 0.13, 2);
        $totalPagar = $totalGravada + $iva;

        $resumen = [
            "totalNoSuj" => 0,
            "totalExenta" => 0,
            "totalGravada" => round($totalGravada, 2),
            "subTotalVentas" => round($totalGravada, 2),
            "descuNoSuj" => 0,
            "descuExenta" => 0,
            "descuGravada" => 0,
            "porcentajeDescuento" => 0,
            "totalDescu" => 0,
            "tributos" => [
                [
                    "codigo" => "20",
                    "descripcion" => "Impuesto al Valor Agregado 13%",
                    "valor" => $iva
                ]
            ],
            "subTotal" => round($totalGravada, 2),
            "ivaPerci1" => 0,
            "ivaRete1" => 0,
            "reteRenta" => 0,
            "montoTotalOperacion" => $totalPagar,
            "totalNoGravado" => 0,
            "totalPagar" => $totalPagar,
            "totalLetras" => $this->convertirALetras($totalPagar),
            "saldoFavor" => 0,
            "condicionOperacion" => 1,
            "pagos" => [
                [
                    "codigo" => "01",
                    "montoPago" => $totalPagar,
                    "plazo" => null,
                    "referencia" => "",
                    "periodo" => null
                ]
            ],
            "numPagoElectronico" => null
        ];

        return [
            'cuerpoDocumento' => $items,
            'resumen' => $resumen
        ];
    }

    private function generarDetalleYResumenExportacion(): array
    {
        $numItems = rand(1, 2);
        $items = [];
        $totalGravada = 0;

        for ($i = 1; $i <= $numItems; $i++) {
            $precio = rand(100, 800);
            $cantidad = rand(1, 3);
            $venta = round($precio * $cantidad, 2);

            $items[] = [
                "numItem" => $i,
                "cantidad" => $cantidad,
                "codigo" => null,
                "uniMedida" => 59,
                "descripcion" => "Item Exportación #" . $i,
                "precioUni" => $precio,
                "montoDescu" => 0,
                "ventaGravada" => $venta,
                "tributos" => ["C3"],
                "noGravado" => 0
            ];

            $totalGravada += $venta;
        }

        $resumen = [
            "totalGravada" => round($totalGravada, 2),
            "descuento" => 0,
            "porcentajeDescuento" => 0,
            "totalDescu" => 0,
            "seguro" => 0,
            "flete" => 0,
            "montoTotalOperacion" => $totalGravada,
            "totalNoGravado" => 0,
            "totalPagar" => $totalGravada,
            "totalLetras" => $this->convertirALetras($totalGravada),
            "condicionOperacion" => 1,
            "pagos" => [
                [
                    "codigo" => "01",
                    "montoPago" => $totalGravada,
                    "plazo" => null,
                    "referencia" => "",
                    "periodo" => null
                ]
            ],
            "numPagoElectronico" => null,
            "codIncoterms" => "09",
            "descIncoterms" => "FOB-Libre a bordo",
            "observaciones" => null
        ];

        return [
            'cuerpoDocumento' => $items,
            'resumen' => $resumen
        ];
    }


    private function convertirALetras($valor): string
    {
        return strtoupper(number_format($valor, 2, '.', ',')) . " DÓLARES";
    }


    private function firmarDTE(array $dteJson, $seguridad): array
    {
        $client = \Config\Services::curlrequest();
        $payload = [
            "nit" => $seguridad['nit'],
            "passwordPri" => $seguridad['passwordFirma'],
            "activo" => true,
            "dteJson" => $dteJson
        ];

        try {
            $response = $client->post($seguridad['urlFirmador'], [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($payload)
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return [
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ];
        }
    }

    private function generarUUID(): string
    {
        return sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function vistaTest()
    {
        return view('nota_debito_view');
    }

    private function extraerCorrelativoDeNumeroControl(string $numeroControl): int
    {
        if (preg_match('/(\d{15})$/', $numeroControl, $match)) {
            return (int)ltrim($match[1], '0');
        }
        return 0;
    }

    public function getInvalidarDTEInfo($codigoGeneracion)
    {

        $sellosDTEmodel = new SellosdteModel();

        $infoDTE = $sellosDTEmodel->getInvalidarDTEInfo($codigoGeneracion);
        $listDteToInvalidate = $sellosDTEmodel->getDteParaReemplazar($codigoGeneracion);

        $dteInfo = [
            'infoDTE' => $infoDTE,
            'listDTE' => $listDteToInvalidate
        ];
        return $this->respond($dteInfo);

    }

    public function procesarInvalidarDTE()
    {

        try{
            $sellosDTEmodel = new SellosdteModel();

            $codigoGeneracion = $this->request->getPost('dteUUID');

            if ($codigoGeneracion === '') {
                return $this->response->setJSON([
                    'success' => false,
                    'error'   => true,
                    'message' => 'No se recibió el código de generación del DTE.',
                    'detalle' => ['El campo dteUUID es obligatorio.']
                ])->setStatusCode(400);
            }

            $tipoAnulacion = (string) $this->request->getPost('mhc_invalidation_type_id');

            $motivoAnulacion = '';
            switch ($tipoAnulacion) {
                case '1':
                    $motivoAnulacion = 'Error en la Información del Documento Tributario Electrónico a invalidar.';
                    break;
                case '2':
                    $motivoAnulacion = 'Rescindir de la operación realizada.';
                    break;
                case '3':
                    $motivoAnulacion = 'Otro';
                    break;
                default:
                    return $this->response->setJSON([
                        'success' => false,
                        'error'   => true,
                        'message' => 'Tipo de invalidación no válido.',
                        'detalle' => ['Debe seleccionar un motivo de invalidación correcto.']
                    ])->setStatusCode(400);
            }

            $dataMotivo = [
                'tipoAnulacion'      => $tipoAnulacion,
                'motivoAnulacion'    => $motivoAnulacion,
                'nombreResponsable'  => trim((string) $this->request->getPost('issuer_name')),
                'tipDocResponsable'  => trim((string) $this->request->getPost('issuer_document_type')),
                'numDocResponsable'  => trim((string) $this->request->getPost('issuer_document_number')),
                'nombreSolicita'     => trim((string) $this->request->getPost('receiver_name')),
                'tipDocSolicita'     => trim((string) $this->request->getPost('mhc_receiver_document_type_id')),
                'numDocSolicita'     => trim((string) $this->request->getPost('receiver_document_number')),
                'codigoGeneracionR'  => trim((string) $this->request->getPost('code_generation_r')),
            ];

            $dataSeguridad = $this->modelSeguridad->getConfigByEnvironment();

            if (empty($dataSeguridad)) {
                return $this->response->setJSON([
                    'success' => false,
                    'error'   => true,
                    'message' => 'No se encontró la configuración de seguridad.',
                    'detalle' => ['Verifique la configuración del ambiente.']
                ])->setStatusCode(500);
            }

            $dteInfo = $sellosDTEmodel->getInforDteToInvalidate($codigoGeneracion);

            if (empty($dteInfo)) {
                return $this->response->setJSON([
                    'success' => false,
                    'error'   => true,
                    'message' => 'No se encontró información del DTE a invalidar.',
                    'detalle' => ['No existen datos asociados al código de generación proporcionado.']
                ])->setStatusCode(404);
            }

            // 1. Generar invalidación
            $invalidacionDte = $this->generarInvalidacion($dataMotivo, $dteInfo, $dataSeguridad);

            if (empty($invalidacionDte)) {
                return $this->response->setJSON([
                    'success' => false,
                    'error'   => true,
                    'message' => 'No fue posible generar el JSON de invalidación.',
                    'detalle' => ['El método generarInvalidacion devolvió una respuesta vacía.']
                ])->setStatusCode(500);
            }

            // 2. Firmar DTE
            $firma = $this->firmarDTE($invalidacionDte, $dataSeguridad);

            if (
                empty($firma) ||
                !is_array($firma) ||
                empty($firma['body'])
            ) {
                return $this->response->setJSON([
                    'success' => false,
                    'error'   => true,
                    'message' => 'No fue posible firmar el DTE de invalidación.',
                    'detalle' => [
                        'No se recibió una firma válida desde el servicio firmador.'
                    ]
                ])->setStatusCode(500);
            }

            // 3. Obtener token
            $token = $this->validarToken($dataSeguridad);

            if (empty($token) || !is_string($token)) {
                return $this->response->setJSON([
                    'success' => false,
                    'error'   => true,
                    'message' => 'No fue posible obtener el token de autenticación.',
                    'detalle' => [
                        'El método validarToken no devolvió un token válido.'
                    ]
                ])->setStatusCode(500);
            }

            // 4. Enviar a MH
            $sello = $this->enviarASelloMHInvalidacion(
                $firma['body'],
                $dteInfo['numeroControl'],
                $token,
                $dataSeguridad
            );

            if (empty($sello->getbody()) || !is_array(json_decode($sello->getbody(), true))) {
                return $this->response->setJSON([
                    'success' => false,
                    'error'   => true,
                    'message' => 'No se recibió respuesta del Ministerio de Hacienda.',
                    'detalle' => [
                        'La respuesta del endpoint de invalidación llegó vacía o en formato inesperado.'
                    ]
                ])->setStatusCode(502);
            }

            $sello = json_decode($sello->getbody(),true);

            $estadoMH = strtoupper((string)($sello['estado'] ?? ''));

            if ($estadoMH === 'RECHAZADO') {
                $detalle = [];

                if (!empty($sello['descripcionMsg'])) {
                    $detalle[] = $sello['descripcionMsg'];
                }

                if (!empty($sello['observaciones']) && is_array($sello['observaciones'])) {
                    $detalle = array_merge($detalle, $sello['observaciones']);
                }

                return $this->response->setJSON([
                    'success' => false,
                    'error'   => true,
                    'message' => 'Hacienda rechazó la invalidación del DTE.',
                    'detalle' => !empty($detalle) ? $detalle : ['El MH devolvió estado RECHAZADO sin observaciones adicionales.'],
                    'data'    => $sello
                ])->setStatusCode(422);
            }

            // 5. Actualizar BD
            $result = $this->actualizarAnulacionEnBD($sello, $firma, $dteInfo, $invalidacionDte);

            if ($result === false) {
                return $this->response->setJSON([
                    'success' => false,
                    'error'   => true,
                    'message' => 'La invalidación fue enviada correctamente, pero no se pudo actualizar la base de datos.',
                    'detalle' => ['Revise el método actualizarAnulacionEnBD.']
                ])->setStatusCode(500);
            }

            return $this->response->setJSON([
                'success' => true,
                'error'   => false,
                'message' => 'El DTE fue invalidado correctamente.',
                'detalle' => [],
                'data'    => [
                    'codigoGeneracion' => $codigoGeneracion,
                    'estado'           => $sello['estado'] ?? 'PROCESADO'
                ]
            ]);


        } catch (\Exception $e) {
            log_message('error', 'Error al invalidar DTE: ' . $e->getMessage() . ' | Línea: ' . $e->getLine());

            return $this->response->setJSON([
                'success' => false,
                'error'   => true,
                'message' => 'Ocurrió una excepción inesperada durante la invalidación.',
                'detalle' => [
                    $e->getMessage()
                ]
            ])->setStatusCode(500);
        }
    }

    private function generarInvalidacion($dataMotivo, $dteInfo, $seguridad)
    {

        date_default_timezone_set('America/El_Salvador');

        return [
            "identificacion" => [
                "version" => 2,
                "ambiente" => $seguridad['ambiente'],
                "codigoGeneracion" => strtoupper($this->generarUUID()),
                "fecAnula" => date('Y-m-d'),
                "horAnula" => date('H:i:s')
            ],
            "emisor" => [
                "nit" => $dteInfo['nitEmisor'],
                "nombre" => $dteInfo['nombreEmisor'],
                "tipoEstablecimiento" => $dteInfo['tipoEstablecimientoEmidor'],
                "nomEstablecimiento" => $dteInfo['nombreEmisor'],
                "codEstableMH" => $dteInfo['codEstableMHEmisor'],
                "codEstable" => $dteInfo['codEstableEmisor'],
                "codPuntoVentaMH" => $dteInfo['codPuntoVentaMHEmisor'],
                "codPuntoVenta" => $dteInfo['codPuntoVentaEmisor'],
                "telefono" => str_replace('-', '', $dteInfo['telefonoEmisor']),
                "correo" => $dteInfo['correoEmisor']
            ],
            "documento" => [
                "tipoDte" => $dteInfo['tipoDte'],
                "codigoGeneracion" => $dteInfo['codigoGeneracion'],
                "selloRecibido" => $dteInfo['selloRecibido'],
                "numeroControl" => $dteInfo['numeroControl'],
                "fecEmi" => $dteInfo['fechaEmision'],
                "montoIva" => ($dteInfo['totalIva'] == "0") ? 0.00 : (float)number_format($dteInfo['totalIva'], 2, null, null),
                "codigoGeneracionR" => ($dataMotivo['codigoGeneracionR'] == "") ? null : $dataMotivo['codigoGeneracionR'],
                "tipoDocumento" => $dteInfo['tipoDocumentoReceptor'],
                "numDocumento" => $dteInfo['numDocumentoReceptor'],
                "nombre" => $dteInfo['nombreReceptor'],
                "telefono" => str_replace('-', '', $dteInfo['telefonoReceptor']),
                "correo" => $dteInfo['correoReceptor']
            ],
            "motivo" => [
                "tipoAnulacion" => intVal($dataMotivo['tipoAnulacion']),
                "motivoAnulacion" => $dataMotivo['motivoAnulacion'],
                "nombreResponsable" => $dataMotivo['nombreResponsable'],
                "tipDocResponsable" => $dataMotivo['tipDocResponsable'],
                "numDocResponsable" => $dataMotivo['numDocResponsable'],
                "nombreSolicita" => $dataMotivo['nombreSolicita'],
                "tipDocSolicita" => $dataMotivo['tipDocSolicita'],
                "numDocSolicita" => $dataMotivo['numDocSolicita']
            ]
        ];

    }

}