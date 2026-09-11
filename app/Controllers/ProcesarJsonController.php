<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\JsonProcessFileModel;
use App\Models\JsonProcessGroupModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProcesarJsonController extends BaseController
{
    public function index()
    {

        $groupModel = new JsonProcessGroupModel();

        return $this->render('pages/procesar_json/index', [
            'groups' => $groupModel->orderBy('id', 'DESC')->findAll(30),
        ]);
    }

    public function procesar()
    {
        $groupName = trim((string)$this->request->getPost('group_name'));
        $uploadedFiles = $this->request->getFileMultiple('json_files');

        if ($groupName === '') {
            return redirect()->back()->with('error', 'El nombre del grupo es obligatorio.');
        }

        if (empty($uploadedFiles)) {
            return redirect()->back()->with('error', 'Debe cargar al menos un archivo JSON.');
        }

        if (count($uploadedFiles) > 100 || strlen($groupName) > 100) {
            return redirect()->back()->with('error', 'Máximo 100 archivos y 100 caracteres para el grupo.');
        }
        $totalBytes = 0;
        foreach ($uploadedFiles as $uploadedFile) {
            $totalBytes += $uploadedFile->getSize();
            if (!$uploadedFile->isValid() || strtolower($uploadedFile->getClientExtension()) !== 'json'
                || $uploadedFile->getSize() > 2 * 1024 * 1024 || $totalBytes > 20 * 1024 * 1024) {
                return redirect()->back()->with('error', 'Solo JSON: máximo 2 MB por archivo y 20 MB por carga.');
            }
        }

        $safeGroupName = strtolower($groupName);
        $safeGroupName = preg_replace('/\s+/', '_', $safeGroupName);
        $safeGroupName = preg_replace('/[^a-z0-9_\-]/', '_', $safeGroupName);
        $safeGroupName = preg_replace('/_+/', '_', $safeGroupName);
        $safeGroupName = trim($safeGroupName, '_');
        if ($safeGroupName === '') {
            return redirect()->back()->with('error', 'El nombre del grupo debe contener letras o números.');
        }
        $targetFolder = WRITEPATH . 'uploads/json_process/' . $safeGroupName;

        if (is_dir($targetFolder)) {
            return redirect()->back()->with('error', 'Ya existe una carpeta con ese nombre de grupo.');
        }

        if (!mkdir($targetFolder, 0775, true) && !is_dir($targetFolder)) {
            return redirect()->back()->with('error', 'No se pudo crear la carpeta del grupo.');
        }

        $groupModel = new JsonProcessGroupModel();
        $fileModel = new JsonProcessFileModel();

        $groupId = $groupModel->protect(false)->insert([
            'group_name' => $groupName,
            'folder_path' => $targetFolder,
            'processed_files_count' => 0,
        ]);

        $rowsForExcel = [];
        $processedCount = 0;

        foreach ($uploadedFiles as $file) {
            if (!$file->isValid()) {
                continue;
            }

            $originalName = basename(str_replace('\\', '/', $file->getClientName()));
            $storedName = bin2hex(random_bytes(16)) . '.json';
            $file->move($targetFolder, $storedName);
            $newPath = $targetFolder . '/' . $storedName;

            $jsonContent = (string)file_get_contents($newPath);
            // Eliminar BOM UTF-8 si existe
            $jsonContent = preg_replace('/^\xEF\xBB\xBF/', '', $jsonContent);

            // Forzar UTF-8 válido
            $jsonContent = mb_convert_encoding($jsonContent, 'UTF-8', 'UTF-8');
            $jsonData = $this->decodeJsonPayload($jsonContent);
            if (!is_array($jsonData)) {
                continue;
            }

            $appendix = $this->indexAppendix($jsonData['apendice'] ?? []);
            $tributes = $this->indexTributes($jsonData['resumen']['tributos'] ?? []);

            $numeroControlCompleto = $jsonData['identificacion']['numeroControl'] ?? '';
            $controlNumberParts = explode('-', $numeroControlCompleto);
            $numeroControl = !empty($numeroControlCompleto) ? end($controlNumberParts) : null;

            $record = [
                'group_id' => $groupId,
                'file_name' => $originalName,
                'generation_code' => $jsonData['identificacion']['codigoGeneracion'] ?? null,
                'issue_date' => $jsonData['identificacion']['fecEmi'] ?? null,
                'register' => $jsonData['emisor']['nrc'] ?? null,
                'nit' => $jsonData['emisor']['nit'] ?? null,
                'issuer_name' => $jsonData['emisor']['nombre'] ?? null,
                'numero_control' => $numeroControl,
                'gravadas'=> $jsonData['resumen']['totalGravada'] ?? 0,
                'exentas'=> $jsonData['resumen']['totalExenta'] ?? 0,
                'iva_1' => $jsonData['resumen']['ivaPerci1'] ?? 0,
                'iva_13' => ($tributes['20'] ?? 0),
                'fovial' => ($tributes['D1'] ?? 0),
                'cotrans' => ($tributes['C8'] ?? 0),
                'total_pagar' => $jsonData['resumen']['totalPagar'] ?? 0,
                'sello_recibido' => $jsonData['selloRecibido'] ?? null,
                'moved_path' => $newPath,
            ];

            $fileModel->insert($record);
            $rowsForExcel[] = $record;
            $processedCount++;
        }

        $excelPath = $targetFolder . '/resumen_' . $safeGroupName . '.csv';
        $fp = fopen($excelPath, 'w');
        fputcsv($fp, [
            'Correlativo',
            'Archivo',
            'Codigo de Generacion',
            'Sello recibido',
            'Fecha de Emision',
            'Registro',
            'NIT',
            'Emisor',
            'Numero de Control',
            'Gravadas',
            'Exentas',
            'Percepcion Iva 1%',
            'IVA',
            'Total',
            'Compras Sujetos Excluidos Iva',
            'CLASE DE DOCUMENTO'
        ]);

        foreach ($rowsForExcel as $index => $row) {
            foreach ($row as &$value) {
                if (is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value)) {
                    $value = "'" . $value;
                }
            }
            unset($value);
            fputcsv($fp, [
                $index + 1,
                $row['file_name'],
                $row['generation_code'],
                $row['sello_recibido'],
                $row['issue_date'],
                $row['register'],
                $row['nit'],
                $row['issuer_name'],
                $row['numero_control'],
                $row['gravadas'],
                $row['exentas'],
                $row['iva_1'],
                ($row['iva_13'] ?? 0) + ($row['fovial'] ?? 0),
                $row['total_pagar'],
                '',
                '4. DOCUMENTO TRIBUTARIO ELECTRONICO (DTE)'
            ]);
        }
        fclose($fp);

        $groupModel->protect(false)->update($groupId, [
            'processed_files_count' => $processedCount,
            'excel_file_path' => $excelPath,
        ]);

        return redirect()->to(base_url('procesar-json'))->with('success', 'Archivos procesados correctamente.');
    }

    public function descargarCsv(int $groupId)
    {
        $groupModel = new JsonProcessGroupModel();
        $group = $groupModel->find($groupId);

        if (!$group || empty($group['excel_file_path'])) {
            return redirect()->back()->with('error', 'No se encontró el archivo CSV del grupo.');
        }

        $excelPath = $group['excel_file_path'];
        if (!is_file($excelPath)) {
            return redirect()->back()->with('error', 'El archivo CSV ya no existe en disco.');
        }

        return $this->response->download($excelPath, null)->setFileName(basename($excelPath));
    }

    public function descargarExcelPhpSpreadsheet(int $groupId)
    {
        $groupModel = new JsonProcessGroupModel();
        $fileModel = new JsonProcessFileModel();

        $group = $groupModel->find($groupId);

        if (!$group) {
            return redirect()
                ->back()
                ->with('error', 'Grupo no encontrado.');
        }

        $files = $fileModel
            ->where('group_id', $groupId)
            ->findAll();

        if (empty($files)) {
            return redirect()
                ->back()
                ->with('error', 'No existen registros para exportar.');
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Libro Compras');

        $sheet->mergeCells('A1:P1');

        $sheet->setCellValue(
            'A1',
            'MEGALOAD S.A. DE C.V.'
        );

        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);

        $sheet->getStyle('A1')->getAlignment()->setHorizontal(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        );

        $sheet->getStyle('A1')->getAlignment()->setVertical(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        );


        $sheet->mergeCells('A2:P2');

        $sheet->setCellValue(
            'A2',
            'Libro de Compras'
        );

        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getFont()->setSize(12);

        $sheet->getStyle('A2')->getAlignment()->setHorizontal(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        );

        $sheet->getStyle('A2')->getAlignment()->setVertical(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        );

        $meses = [
            1 => 'ENERO',
            2 => 'FEBRERO',
            3 => 'MARZO',
            4 => 'ABRIL',
            5 => 'MAYO',
            6 => 'JUNIO',
            7 => 'JULIO',
            8 => 'AGOSTO',
            9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE'
        ];

        $mesActual = $meses[(int)date('n')];
        $anioActual = date('Y');

        $sheet->setCellValue(
            'C6',
            'PERIODO TRIBUTARIO: ' . $mesActual
        );

        $sheet->setCellValue(
            'J6',
            'AÑO: ' . $anioActual
        );

        $sheet->getStyle('C6')->getFont()->setBold(true);
        $sheet->getStyle('J6')->getFont()->setBold(true);
        $sheet->mergeCells('A3:P3');

        $sheet->setCellValue(
            'A3',
            'Registro De IVA Nº 247310-9'
        );

        $sheet->getStyle('A3')->getFont()->setBold(true);

        $sheet->getStyle('A3')->getFont()->setSize(11);

        $sheet->getStyle('A3')->getAlignment()->setHorizontal(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        );

        $sheet->getStyle('A3')->getAlignment()->setVertical(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        );

        $sheet->getRowDimension(3)->setRowHeight(20);

        /*
|--------------------------------------------------------------------------
| ENCABEZADOS TABLA
|--------------------------------------------------------------------------
*/

        $sheet->setCellValue('A8', 'Correlativo');
        $sheet->setCellValue('B8', 'Fecha');
        $sheet->setCellValue('C8', 'Registro');
        $sheet->setCellValue('D8', 'NIT');
        $sheet->setCellValue('E8', 'Nombre del Emisor');
        $sheet->setCellValue('F8', 'Numero de Control');

        $sheet->mergeCells('G8:H8');
        $sheet->setCellValue('G8', 'Gravadas');

        $sheet->mergeCells('I8:J8');
        $sheet->setCellValue('I8', 'Exentas');

        $sheet->setCellValue('K8', 'Percepcion Iva 1%');
        $sheet->setCellValue('L8', 'IVA');
        $sheet->setCellValue('M8', 'Total');
        $sheet->setCellValue('N8', 'Compras Sujetos Excluidos Iva');
        $sheet->setCellValue('O8', 'CLASE DE DOCUMENTO');

        $sheet->getStyle('A8:O8')->getFont()->setBold(true);
        $sheet->getStyle('A8:O8')->getFont()->setSize(11);

        $sheet->getStyle('A8:O8')->getAlignment()->setHorizontal(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        );

        $sheet->getStyle('A8:O8')->getAlignment()->setVertical(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        );

        $sheet->getStyle('A8:O8')->getAlignment()->setWrapText(true);

        $sheet->getStyle('A8:O8')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('B7DEE8');

        $sheet->getStyle('A8:O8')->getBorders()->getAllBorders()->setBorderStyle(
            \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
        );

        $sheet->getRowDimension(8)->setRowHeight(35);

        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(40);
        $sheet->getColumnDimension('F')->setWidth(22);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(10);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(10);
        $sheet->getColumnDimension('K')->setWidth(18);
        $sheet->getColumnDimension('L')->setWidth(15);
        $sheet->getColumnDimension('M')->setWidth(15);
        $sheet->getColumnDimension('N')->setWidth(28);
        $sheet->getColumnDimension('O')->setWidth(30);

        /*
        |--------------------------------------------------------------------------
        | DATA DESDE BASE DE DATOS
        |--------------------------------------------------------------------------
        */

        $rowNumber = 9;

        $totalGravadas = 0;
        $totalExentas = 0;
        $totalIva1 = 0;
        $totalIva = 0;
        $totalPagar = 0;
        $totalSujetosExcluidos = 0;

        foreach ($files as $index => $file) {

            $issueDate = '';

            if (!empty($file['issue_date'])) {
                $issueDate = date('m/d/Y', strtotime($file['issue_date']));
            }

            $numeroControl = (string)($file['numero_control'] ?? '');

            $gravadas = (float)($file['gravadas'] ?? 0);
            $exentas = (float)($file['exentas'] ?? 0);
            $iva1 = (float)($file['iva_1'] ?? 0);
            $iva = (float)($file['iva_13'] ?? 0);
            $total = (float)($file['total_pagar'] ?? 0);
            $sujetosExcluidos = 0;

            $sheet->setCellValue('A' . $rowNumber, $index + 1);
            $sheet->setCellValue('B' . $rowNumber, $issueDate);
            $sheet->setCellValueExplicit('C' . $rowNumber, $file['register'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $rowNumber, $file['nit'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $rowNumber, $file['issuer_name'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

            $sheet->setCellValueExplicit(
                'F' . $rowNumber,
                $numeroControl,
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );

            // Gravadas ocupa G:H, pero el valor va en G
            $sheet->setCellValue('G' . $rowNumber, $gravadas);

            // Exentas ocupa I:J, pero el valor va en I
            $sheet->setCellValue('I' . $rowNumber, $exentas);

            $sheet->setCellValue('K' . $rowNumber, $iva1);
            $sheet->setCellValue('L' . $rowNumber, $iva);
            $sheet->setCellValue('M' . $rowNumber, $total);
            $sheet->setCellValue('N' . $rowNumber, $sujetosExcluidos);
            $sheet->setCellValue('O' . $rowNumber, '4. DOCUMENTO TRIBUTARIO ELECTRONICO (DTE)');

            $totalGravadas += $gravadas;
            $totalExentas += $exentas;
            $totalIva1 += $iva1;
            $totalIva += $iva;
            $totalPagar += $total;
            $totalSujetosExcluidos += $sujetosExcluidos;

            $rowNumber++;
        }

        /*
|--------------------------------------------------------------------------
| FILA VACÍA Y FILA DE TOTALES
|--------------------------------------------------------------------------
*/

// Última fila con datos
        $lastDataRow = $rowNumber - 1;

// Fila vacía
        $rowNumber++;

// Fila total
        $totalRow = $rowNumber;

        $sheet->setCellValue('E' . $totalRow, 'TOTAL');

        $sheet->setCellValue('G' . $totalRow, '=SUM(G9:G' . $lastDataRow . ')');
        $sheet->setCellValue('I' . $totalRow, '=SUM(I9:I' . $lastDataRow . ')');
        $sheet->setCellValue('K' . $totalRow, '=SUM(K9:K' . $lastDataRow . ')');
        $sheet->setCellValue('L' . $totalRow, '=SUM(L9:L' . $lastDataRow . ')');
        $sheet->setCellValue('M' . $totalRow, '=SUM(M9:M' . $lastDataRow . ')');
        $sheet->setCellValue('N' . $totalRow, '=SUM(N9:N' . $lastDataRow . ')');

        $sheet->getStyle('A' . $totalRow . ':O' . $totalRow)
            ->getFont()
            ->setBold(true);

        $sheet->getStyle('A' . $totalRow . ':O' . $totalRow)
            ->getBorders()
            ->getTop()
            ->setBorderStyle(
                \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
            );

        /*
        |--------------------------------------------------------------------------
        | FORMATOS
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle('F9:F' . $lastDataRow)
            ->getNumberFormat()
            ->setFormatCode('@');

        // Formato moneda dólar para columnas numéricas
        $currencyFormat = '$ #,##0.00';

        $sheet->getStyle('G9:G' . $totalRow)
            ->getNumberFormat()
            ->setFormatCode($currencyFormat);

        $sheet->getStyle('I9:I' . $totalRow)
            ->getNumberFormat()
            ->setFormatCode($currencyFormat);

        $sheet->getStyle('K9:N' . $totalRow)
            ->getNumberFormat()
            ->setFormatCode($currencyFormat);

        $sheet->getStyle('A8:O' . $totalRow)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
            );

        $sheet->setCellValue('G' . $totalRow, '=SUM(G9:G' . $lastDataRow . ')'); // Importación
        $sheet->setCellValue('H' . $totalRow, '=SUM(H9:H' . $lastDataRow . ')'); // Locales

        /*
|--------------------------------------------------------------------------
| TABLA RESUMEN IMPORTACION / LOCALES
|--------------------------------------------------------------------------
*/

        $summaryStartRow = $totalRow + 3;

        $labelCol = 'E';
        $gravadasCol = 'F';
        $ivaCol = 'G';

        $headerRow = $summaryStartRow;
        $importacionRow = $summaryStartRow + 1;
        $localesRow = $summaryStartRow + 2;
        $totalesRow = $summaryStartRow + 3;

// Encabezados
        $sheet->setCellValue($gravadasCol . $headerRow, 'GRAVADAS');
        $sheet->setCellValue($ivaCol . $headerRow, 'IVA');

// Labels
        $sheet->setCellValue($labelCol . $importacionRow, 'IMPORTACION');
        $sheet->setCellValue($labelCol . $localesRow, 'LOCALES');
        $sheet->setCellValue($labelCol . $totalesRow, 'TOTALES');

// Valores con fórmulas
        $sheet->setCellValue($gravadasCol . $importacionRow, '=G' . $totalRow);
        $sheet->setCellValue($ivaCol . $importacionRow, '=' . $gravadasCol . $importacionRow . '*0.13');

        $sheet->setCellValue($gravadasCol . $localesRow, '=H' . $totalRow);
        $sheet->setCellValue($ivaCol . $localesRow, '=' . $gravadasCol . $localesRow . '*0.13');

        $sheet->setCellValue($gravadasCol . $totalesRow, '=SUM(' . $gravadasCol . $importacionRow . ':' . $gravadasCol . $localesRow . ')');
        $sheet->setCellValue($ivaCol . $totalesRow, '=SUM(' . $ivaCol . $importacionRow . ':' . $ivaCol . $localesRow . ')');

// Estilos
        $sheet->getStyle($gravadasCol . $headerRow . ':' . $ivaCol . $headerRow)
            ->getFont()
            ->setBold(true);

        $sheet->getStyle($labelCol . $totalesRow . ':' . $ivaCol . $totalesRow)
            ->getFont()
            ->setBold(true);

        $sheet->getStyle($labelCol . $headerRow . ':' . $ivaCol . $totalesRow)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $sheet->getStyle($labelCol . $headerRow . ':' . $ivaCol . $totalesRow)
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle($gravadasCol . $importacionRow . ':' . $ivaCol . $totalesRow)
            ->getNumberFormat()
            ->setFormatCode('$#,##0.00');

        $sheet->getColumnDimension($labelCol)->setWidth(18);
        $sheet->getColumnDimension($gravadasCol)->setWidth(15);
        $sheet->getColumnDimension($ivaCol)->setWidth(15);


        $fileName = 'libro_compras_' . date('Ymd_His') . '.xlsx';

        $tempPath = WRITEPATH . 'uploads/temp/';

        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0775, true);
        }

        $fullPath = $tempPath . $fileName;

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $writer->save($fullPath);

        return $this->response
            ->download($fullPath, null)
            ->setFileName($fileName);
    }

    public function eliminarGrupo(int $groupId)
    {
        $groupModel = new JsonProcessGroupModel();
        $filesGroup = new JsonProcessFileModel();

        $group = $groupModel->find($groupId);

        if (!$group) {
            return redirect()->back()->with('error', 'El grupo no existe.');
        }

        if (!empty($group['folder_path']) && is_dir($group['folder_path'])) {
            $this->deleteDirectoryRecursively($group['folder_path']);
        }

        $groupModel->delete($groupId);
        $filesGroup->select('id')->where('group_id', $groupId)->delete();

        return redirect()->to(base_url('procesar-json'))->with('success', 'Grupo eliminado correctamente.');
    }

    private function decodeJsonPayload(string $payload): ?array
    {
        // Intento normal
        $decoded = json_decode($payload, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Limpiar espacios
        $trimmed = trim($payload);

        // Buscar múltiples JSON concatenados
        $positions = [
            strpos($trimmed, "}\n{"),
            strpos($trimmed, '}{')
        ];

        foreach ($positions as $end) {

            if ($end !== false) {

                $firstJson = substr($trimmed, 0, $end + 1);

                $decoded = json_decode($firstJson, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        // Debug opcional
        log_message('error', 'JSON ERROR: ' . json_last_error_msg());

        return null;
    }

    private function normalizeObservaciones(array $observaciones): ?string
    {
        if (empty($observaciones)) {
            return null;
        }

        return implode(' | ', array_map('strval', $observaciones));
    }

    private function indexAppendix(array $appendix): array
    {
        $indexed = [];
        foreach ($appendix as $item) {
            if (isset($item['campo'])) {
                $indexed[$item['campo']] = $item['valor'] ?? null;
            }
        }
        return $indexed;
    }

    private function indexTributes(array $tributes): array
    {
        $indexed = [];
        foreach ($tributes as $tribute) {
            if (isset($tribute['codigo'])) {
                $indexed[$tribute['codigo']] = $tribute['valor'] ?? 0;
            }
        }
        return $indexed;
    }

    private function deleteDirectoryRecursively(string $directory): void
    {
        $basePath = realpath(WRITEPATH . 'uploads/json_process');
        $targetPath = realpath($directory);

        if ($basePath === false || $targetPath === false || strpos($targetPath, $basePath) !== 0) {
            return;
        }

        $items = scandir($targetPath);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $targetPath . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectoryRecursively($path);
            } elseif (is_file($path)) {
                @unlink($path);
            }
        }

        @rmdir($targetPath);
    }


}
