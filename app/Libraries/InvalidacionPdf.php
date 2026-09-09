<?php

namespace App\Libraries;

use TCPDF;

class InvalidacionPdf
{
    /** Lee el evento conservado en el JWS local; no verifica su firma criptográfica. */
    public function obtenerEvento(array $registro): object
    {
        $respuesta = json_decode($registro['jsonAnulacion'] ?? '');
        if (($respuesta->estado ?? '') !== 'PROCESADO' || empty($respuesta->selloRecibido)) {
            throw new \DomainException('El DTE no tiene una invalidación aceptada por Hacienda.');
        }
        $parts = explode('.', (string) ($registro['firmaAnulacion'] ?? ''));
        $payload = count($parts) === 3 ? base64_decode(strtr($parts[1], '-_', '+/'), true) : false;
        $evento = $payload === false ? null : json_decode($payload);
        if (!is_object($evento) || !isset($evento->identificacion, $evento->emisor, $evento->documento, $evento->motivo)
            || empty($evento->identificacion->codigoGeneracion)
            || ($evento->documento->codigoGeneracion ?? null) !== ($registro['codigoGeneracion'] ?? null)
            || ($respuesta->codigoGeneracion ?? null) !== $evento->identificacion->codigoGeneracion) {
            throw new \UnexpectedValueException('No se pudo recuperar el evento de invalidación correspondiente al DTE.');
        }
        return $evento;
    }

    public function generar(object $evento, object $respuesta): TCPDF
    {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetLineWidth(0.1);
        $pdf->SetTitle('Evento de invalidación - ' . $evento->identificacion->codigoGeneracion);
        $pdf->SetAuthor($evento->emisor->nombre ?? '');
        $pdf->AddPage();
        $e = static fn ($v): string => nl2br(htmlspecialchars((string) ($v ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $docLabel = static fn ($code): string => ['36' => 'NIT', '13' => 'DUI', '02' => 'Carné de residente',
            '03' => 'Pasaporte', '37' => 'Otro documento'][$code ?? ''] ?? 'Documento';
        $rows = static function (array $fields) use ($e): string {
            $html = '<table cellpadding="3" cellspacing="0">';
            foreach ($fields as [$label, $value]) {
                $html .= '<tr><td width="40%">' . $e($label) . ':</td><td width="60%">' . $e($value) . '</td></tr>';
            }
            return $html . '</table>';
        };
        $section = static fn ($title, $body): string => '<h3 style="font-weight:normal">' . $title
            . '</h3><table border="1" cellpadding="3" cellspacing="0"><tr><td>' . $body . '</td></tr></table>';
        $id = $evento->identificacion;
        $emisor = $evento->emisor;
        $doc = $evento->documento;
        $motivo = $evento->motivo;
        $pdf->SetXY(10, 8);
        $pdf->Cell(190, 4, 'Ver. ' . $id->version, 0, 0, 'R');
        $logo = dirname(__DIR__, 2) . '/public/images/logo_megaload_only_img.png';
        if (is_file($logo)) {
            $pdf->Image($logo, 38, 11, 20, 20, 'PNG');
        }
        $pdf->SetFont('helvetica', '', 7.5);
        $brand = '<div align="center"><b>' . $e($emisor->nombre) . '</b><br>'
            . 'NIT: ' . $e($emisor->nit) . '<br>'
            . $e($emisor->correo ?? null) . ', Tel: ' . $e($emisor->telefono ?? null)
            . '<br>https://www.grupomegaload.com/</div>';
        $pdf->writeHTMLCell(78, 0, 10, 32, $brand, 0, 1);
        $brandEnd = $pdf->GetY();
        $header = '<div align="center"><b>Documento Tributario Electrónico<br>EVENTO DE INVALIDACIÓN</b></div><br>'
            . 'Código de generación: <b>' . $e($id->codigoGeneracion) . '</b><br>'
            . 'Fecha y hora del evento: <b>' . $e($id->fecAnula . ' ' . $id->horAnula) . '</b><br>'
            . 'Sello de recepción: <b>' . $e($respuesta->selloRecibido) . '</b><br><br>'
            . '<table border="1" cellpadding="2" cellspacing="0"><tr bgcolor="#dddddd">'
            . '<td width="35%">Estado del evento</td><td width="65%">Fecha y hora de recepción</td></tr><tr>'
            . '<td width="35%">' . $e($respuesta->estado ?? null) . '</td><td width="65%">'
            . $e($respuesta->fhProcesamiento ?? null) . '</td></tr></table>';
        $pdf->SetFont('helvetica', '', 7);
        $pdf->writeHTMLCell(106, 0, 92, 15, $header, 0, 1);
        $headerEnd = max(51, $pdf->GetY() + 2, $brandEnd + 2);
        $pdf->RoundedRect(90, 13, 110, $headerEnd - 13, 1);
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->Line(10, $headerEnd + 3, 200, $headerEnd + 3);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetY($headerEnd + 6);
        $partyY = $pdf->GetY();
        $pdf->writeHTMLCell(94, 0, 10, $partyY, $section('Identificación del emisor', $rows([
            ['Nombre', $emisor->nombre], ['NIT', $emisor->nit], ['Teléfono', $emisor->telefono ?? null],
            ['Correo electrónico', $emisor->correo ?? null],
        ])), 0, 1);
        $emisorEnd = $pdf->GetY();
        $pdf->writeHTMLCell(94, 0, 106, $partyY, $section('Identificación del receptor', $rows([
            ['Nombre o razón social', $doc->nombre ?? null], [$docLabel($doc->tipoDocumento ?? null), $doc->numDocumento ?? null],
            ['Correo electrónico', $doc->correo ?? null],
        ])), 0, 1);
        $pdf->SetY(max($emisorEnd, $pdf->GetY()) + 3);
        $tipos = [1 => 'Error en la información del DTE', 2 => 'Rescindir de la operación realizada', 3 => 'Otro'];
        $left = $rows([['Tipo de invalidación', $tipos[$motivo->tipoAnulacion] ?? $motivo->tipoAnulacion],
            ['Motivo', $motivo->motivoAnulacion ?? null], ['Nombre de quien realiza el evento', $motivo->nombreResponsable],
            [$docLabel($motivo->tipDocResponsable), $motivo->numDocResponsable]]);
        $right = $rows([['Nombre de quien solicita el evento', $motivo->nombreSolicita],
            [$docLabel($motivo->tipDocSolicita), $motivo->numDocSolicita]]);
        $pdf->writeHTML($section('Información relativa al motivo de invalidación',
            '<table cellpadding="0"><tr><td width="50%">' . $left . '</td><td width="50%">' . $right . '</td></tr></table>') . '<br><br>');
        $types = ['01' => 'Factura', '03' => 'Crédito fiscal', '04' => 'Nota de remisión', '05' => 'Nota de crédito',
            '06' => 'Nota de débito', '07' => 'Comprobante de retención', '08' => 'Comprobante de liquidación',
            '09' => 'Documento contable de liquidación', '11' => 'Factura de exportación', '14' => 'Factura de sujeto excluido', '15' => 'Comprobante de donación'];
        $columns = [12, 26, 28, 23, 11];
        $html = '<table border="1" cellpadding="4" cellspacing="0"><thead><tr bgcolor="#eeeeee">';
        foreach (['Tipo DTE', 'Código de generación aplicado', 'Sello de recepción', 'Número de control del DTE', 'Fecha de generación'] as $i => $label) {
            $html .= '<th align="center" width="' . $columns[$i] . '%">' . $label . '</th>';
        }
        $html .= '</tr></thead><tr nobr="true">';
        foreach ([$types[$doc->tipoDte] ?? $doc->tipoDte, $doc->codigoGeneracion, $doc->selloRecibido, $doc->numeroControl, $doc->fecEmi] as $i => $value) {
            $html .= '<td width="' . $columns[$i] . '%">' . $e($value) . '</td>';
        }
        $html .= '</tr></table><br><br><br>Código de generación que reemplaza al invalidado: &nbsp; ' . $e($doc->codigoGeneracionR ?? null);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->writeHTML($html);
        $pages = $pdf->getNumPages();
        for ($page = 1; $page <= $pages; $page++) {
            $pdf->setPage($page);
            $pdf->SetAutoPageBreak(false);
            $pdf->RoundedRect(6, 6, 198, 285, 4);
            $pdf->SetXY(10, 286);
            $pdf->Cell(190, 4, 'Página ' . $page . ' de ' . $pages, 0, 0, 'R');
        }
        return $pdf;
    }
}
