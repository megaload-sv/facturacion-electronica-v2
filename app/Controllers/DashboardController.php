<?php

namespace App\Controllers;

use App\Models\SeguridadModel;
use App\Models\SellosdteModel;
use CodeIgniter\Controller;

class DashboardController extends BaseController
{

    private $modelSeguridad;

    public function __construct()
    {
        $this->modelSeguridad = new SeguridadModel();
    }

    public function index()
    {
        $sellosDteModel = new SellosdteModel();


        $dataSeguridad = $this->modelSeguridad->getConfigByEnvironment();

        if (empty($dataSeguridad)) {
            throw new \RuntimeException('No existe configuración de seguridad para el ambiente: ' . ENVIRONMENT);
        }

        //obtener el detalle por un cliente
        $client = \Config\Services::curlrequest();

        $responseFactura = $client->request('GET', $dataSeguridad['urlCountFacturas'],['verify' => true, 'http_errors' => false]);

        //header('Content-Type: application/json');
        $facturas = json_decode($responseFactura->getBody());
        $factProcesadasError = $sellosDteModel->totalDTE();

        $parameters = [
            'facturasProcesadasPendientes' => $facturas->data,
            'facturasProcesadasError' => $factProcesadasError,
            'facturasProcesadasOK' => $sellosDteModel->totalDTEArchivo()
        ];

        // Cargar la vista del dashboard
        return $this->render('pages/dashboard', $parameters);
    }
}