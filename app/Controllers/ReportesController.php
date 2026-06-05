<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\ReportesModel;
use App\Models\MenuModel;

class ReportesController extends BaseController
{

    public function declaraciones()
    {
        return $this->render('reportes/declaraciones');
    }

    public function declaracionesData()
    {
        $inicio = $this->request->getGet('fecha_inicio');
        $fin = $this->request->getGet('fecha_fin');

        $incluirAnulados = $this->request->getGet('incluir_anulados') == '1';

        if (!$inicio || !$fin) {
            return $this->response->setJSON([
                'data' => [],
                'error' => true,
                'message' => 'Debe seleccionar fecha inicio y fecha fin.'
            ]);
        }

        $model = new ReportesModel();
        $data = $model->getDeclaraciones($inicio, $fin, $incluirAnulados);

        return $this->response->setJSON([
            'data' => $data,
            'error' => false
        ]);
    }
}
