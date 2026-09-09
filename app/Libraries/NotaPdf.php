<?php

namespace App\Libraries;

use TCPDF;

/** Representación de los DTE 05 y 06; los importes provienen del DTE sellado. */
class NotaPdf
{
    public function generar(object $dte, ?object $sello, string $condiciones = '', string $invalidado = '', string $cotizacion = '', ?callable $unidad = null): TCPDF
    {
        $id = $dte->identificacion;
        if (!in_array($id->tipoDte, ['05', '06'], true)) {
            throw new \InvalidArgumentException('La plantilla admite únicamente notas de crédito y débito.');
        }
        $titulo = $id->tipoDte === '05' ? 'NOTA DE CRÉDITO' : 'NOTA DE DÉBITO';
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Sistema de facturación');
        $pdf->SetAuthor($dte->emisor->nombre);
        $pdf->SetTitle($titulo . ' - ' . $id->codigoGeneracion);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(5, 8, 5);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->SetLineWidth(0.1);
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->AddPage();
        $e = static function ($value): string {
            $text = preg_replace('/<br\s*\/?>/i', "\n", (string) ($value ?? '-'));
            $text = str_replace(['\\r\\n', '\\n'], "\n", $text);
            return nl2br(htmlspecialchars(strip_tags($text), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        };
        $money = static fn ($value): string => number_format((float) ($value ?? 0), 2, '.', ',');
        $section = static fn ($title): string => '<h4 style="text-align:center">' . $title . '</h4>';
        $table = '<table border="1" cellpadding="2" cellspacing="0" width="100%">';
        $pdf->SetXY(5, 3);
        $pdf->Cell(200, 4, 'Ver. ' . $id->version, 0, 0, 'R');
        $logo = dirname(__DIR__, 2) . '/public/images/logo_megaload_only_img.png';
        if (is_file($logo)) {
            $pdf->Image($logo, 36, 6, 20, 20, 'PNG');
        }
        $brand = '<div style="text-align:center"><b>' . $e($dte->emisor->nombre) . '</b><br>'
            . $e($dte->emisor->direccion->complemento ?? null) . '<br>'
            . $e($dte->emisor->correo ?? null) . ', Tel: ' . $e($dte->emisor->telefono ?? null)
            . '<br>https://www.grupomegaload.com/</div>';
        $pdf->writeHTMLCell(82, 0, 5, 27, $brand, 0, 1);
        $brandEnd = $pdf->GetY();
        $pdf->SetFont('helvetica', '', 6.5);
        $right = '<div style="text-align:center"><b>Documento Tributario Electrónico<br>' . $titulo . '</b></div>'
            . 'Código de generación: <b>' . $e($id->codigoGeneracion) . '</b><br>'
            . 'Número de control del DTE: <b>' . $e($id->numeroControl) . '</b><br>'
            . 'Sello de recepción: <b>' . $e($sello->selloRecibido ?? null) . '</b><br>'
            . 'Fecha de emisión: ' . $e($id->fecEmi . ' ' . $id->horEmi) . ' &nbsp; Moneda: ' . $e($id->tipoMoneda)
            . '<br><table border="1" cellpadding="2"><tr bgcolor="#dddddd"><td width="30%">Tipo de transmisión</td>'
            . '<td width="30%">Modelo de facturación</td><td width="40%">Fecha y hora de recepción</td></tr><tr>'
            . '<td width="30%">' . $e([1 => 'Normal', 2 => 'Por contingencia'][$id->tipoOperacion] ?? $id->tipoOperacion)
            . '</td><td width="30%">' . $e([1 => 'Previo', 2 => 'Diferido'][$id->tipoModelo] ?? $id->tipoModelo)
            . '</td><td width="40%">' . $e($sello->fhProcesamiento ?? null) . '</td></tr></table>';
        $pdf->writeHTMLCell(89, 0, 115, 9, $right, 0, 1);
        $headerEnd = max(47, $pdf->GetY() + 2, $brandEnd + 2);
        $pdf->RoundedRect(90, 8, 115, $headerEnd - 8, 1);
        $url = 'https://admin.factura.gob.sv/consultaPublica?' . http_build_query([
            'ambiente' => $id->ambiente, 'codGen' => $id->codigoGeneracion, 'fechaEmi' => $id->fecEmi,
        ]);
        $pdf->write2DBarcode($url, 'QRCODE,M', 92, 11, 22, 22, ['border' => false, 'padding' => 1]);
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->Line(5, $headerEnd + 2, 205, $headerEnd + 2);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetY($headerEnd + 5);
        $party = static function ($p, bool $issuer) use ($e): string {
            $fields = ['Nombre o razón social' => $p->nombre ?? null, 'NIT' => $p->nit ?? null,
                'NRC' => $p->nrc ?? null, 'Actividad económica' => $p->descActividad ?? null,
                'Dirección' => $p->direccion->complemento ?? null, 'Número de teléfono' => $p->telefono ?? null,
                'Correo electrónico' => $p->correo ?? null, 'Nombre comercial' => $p->nombreComercial ?? null];
            if ($issuer) {
                $fields['Tipo de establecimiento'] = ['01' => 'Sucursal / Agencia', '02' => 'Casa matriz',
                    '04' => 'Bodega', '07' => 'Predio / Patio', '20' => 'Otro'][$p->tipoEstablecimiento ?? ''] ?? ($p->tipoEstablecimiento ?? null);
            }
            $out = '<table border="1" cellpadding="2" cellspacing="0">';
            foreach ($fields as $label => $value) {
                $out .= '<tr><td width="48%">' . $label . ':</td><td width="52%">' . $e($value) . '</td></tr>';
            }
            return $out . '</table>';
        };
        $partyY = $pdf->GetY();
        $pdf->writeHTMLCell(90, 0, 10, $partyY, '<div align="center"><b>EMISOR</b></div>' . $party($dte->emisor, true), 0, 1);
        $issuerEnd = $pdf->GetY();
        $pdf->writeHTMLCell(90, 0, 105, $partyY, '<div align="center"><b>RECEPTOR</b></div>' . $party($dte->receptor, false), 0, 1);
        $pdf->SetY(max($issuerEnd, $pdf->GetY()) + 4);
        $html = $section('VENTA A CUENTA DE TERCEROS') . $table . '<tr><td width="30%"><b>NIT:</b> '
            . $e($dte->ventaTercero->nit ?? null) . '</td><td width="70%"><b>Nombre, denominación o razón social:</b> '
            . $e($dte->ventaTercero->nombre ?? null) . '</td></tr></table>';
        $html .= $section('DOCUMENTOS RELACIONADOS') . $table
            . '<thead><tr><th width="28%"><b>Tipo de documento</b></th><th width="48%"><b>N° de documento</b></th><th width="24%"><b>Fecha del documento</b></th></tr></thead>';
        $types = ['01' => 'Factura', '03' => 'Comprobante de crédito fiscal', '05' => 'Nota de crédito', '06' => 'Nota de débito', '07' => 'Comprobante de retención'];
        foreach ($dte->documentoRelacionado ?? [] as $doc) {
            $html .= '<tr nobr="true"><td width="28%">' . $e($types[$doc->tipoDocumento] ?? $doc->tipoDocumento)
                . '</td><td width="48%">' . $e($doc->numeroDocumento) . '</td><td width="24%">' . $e($doc->fechaEmision) . '</td></tr>';
        }
        $html .= '</table><br><br>' . $table . '<thead><tr style="background-color:#eeeeee">';
        $headers = ['N°' => 4, 'Cantidad' => 9, 'Unidad' => 8, 'Descripción' => 29, 'Precio unitario' => 10,
            'Descuento por ítem' => 10, 'Ventas no sujetas' => 10, 'Ventas exentas' => 10, 'Ventas gravadas' => 10];
        foreach ($headers as $label => $width) {
            $html .= '<th width="' . $width . '%" align="center"><b>' . $label . '</b></th>';
        }
        $html .= '</tr></thead>';
        foreach ($dte->cuerpoDocumento as $item) {
            $values = [$item->numItem, $item->cantidad, $unidad ? $unidad($item->uniMedida) : $item->uniMedida,
                $item->descripcion, $money($item->precioUni), $money($item->montoDescu), $money($item->ventaNoSuj),
                $money($item->ventaExenta), $money($item->ventaGravada)];
            $html .= '<tr nobr="true">';
            foreach ($values as $index => $value) {
                $html .= '<td width="' . array_values($headers)[$index] . '%" align="' . ($index >= 4 ? 'right' : 'left') . '">' . $e($value) . '</td>';
            }
            $html .= '</tr>';
        }
        $r = $dte->resumen;
        $html .= '<tr nobr="true"><td colspan="6" align="right"><b>SUMA DE VENTAS</b></td><td align="right">' . $money($r->totalNoSuj)
            . '</td><td align="right">' . $money($r->totalExenta) . '</td><td align="right">' . $money($r->totalGravada) . '</td></tr></table>';
        $pdf->writeHTML($html);
        $totals = ['Suma total de operaciones' => $r->subTotalVentas,
            'Monto global Desc., Rebajas y otros a ventas no sujetas' => $r->descuNoSuj,
            'Monto global Desc., Rebajas y otros a ventas exentas' => $r->descuExenta,
            'Monto global Desc., Rebajas y otros a ventas gravadas' => $r->descuGravada];
        $html = '<table border="1" cellpadding="2" cellspacing="0" width="100%">';
        $totalRow = static fn ($label, $value): string => '<tr nobr="true"><td width="80%" align="right"><b>' . $e($label) . '</b>'
            . '</td><td width="20%" align="right">' . $money($value) . '</td></tr>';
        foreach ($totals as $label => $value) {
            $html .= $totalRow($label, $value);
        }
        foreach ($r->tributos ?? [] as $tax) {
            $html .= $totalRow($tax->descripcion, $tax->valor);
        }
        foreach (['Sub-Total' => $r->subTotal, 'IVA Percibido' => $r->ivaPerci1 ?? 0,
            'IVA Retenido' => $r->ivaRete1 ?? 0, 'Monto total de la operación' => $r->montoTotalOperacion] as $label => $value) {
            $html .= $totalRow($label, $value);
        }
        $html .= '</table>';
        $notes = 'Cotización: ' . $e($cotizacion) . '<br>Nota: ' . $e($condiciones);
        $summary = '<table border="1" cellpadding="0" cellspacing="0" width="100%"><tr>'
            . '<td width="50%">' . $notes . '</td><td width="50%">' . $html . '</td></tr></table>';
        $pdf->writeHTML($summary);
        $pdf->SetX(5);
        $ext = $dte->extension ?? (object) [];
        $operation = [1 => 'Contado', 2 => 'A crédito', 3 => 'Otro'][$r->condicionOperacion] ?? $r->condicionOperacion;
        $html = '<br>' . $table . '<tr><td><b>Valor en letras:</b> ' . $e($r->totalLetras)
            . '<br><b>Condición de la operación:</b> ' . $e($operation)
            . '<br><b>Observaciones:</b> ' . $e($ext->observaciones ?? null);
        $html .= '</td></tr></table><br><br>' . $table
            . '<tr nobr="true"><td width="70%"><b>Responsable por parte del emisor:</b> ' . $e($ext->nombEntrega ?? null)
            . '</td><td width="30%"><b>N° de documento:</b> ' . $e($ext->docuEntrega ?? null) . '</td></tr>'
            . '<tr nobr="true"><td><b>Responsable por parte del receptor:</b> ' . $e($ext->nombRecibe ?? null)
            . '</td><td><b>N° de documento:</b> ' . $e($ext->docuRecibe ?? null) . '</td></tr></table>';
        $pdf->writeHTML($html);
        $pages = $pdf->getNumPages();
        for ($page = 1; $page <= $pages; $page++) {
            $pdf->setPage($page);
            $pdf->SetAutoPageBreak(false);
            $pdf->SetDrawColor(170, 170, 170);
            $pdf->Rect(2, 2, 206, 293);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->SetXY(5, 289);
            $pdf->Cell(200, 4, 'Página ' . $page . ' de ' . $pages, 0, 0, 'R');
            if ($invalidado !== '') {
                $pdf->StartTransform();
                $pdf->SetAlpha(0.2);
                $pdf->SetTextColor(255, 0, 0);
                $pdf->Rotate(45, 105, 148);
                $pdf->SetFont('helvetica', 'B', 60);
                $pdf->Text(45, 138, 'ANULADO');
                $pdf->StopTransform();
                $pdf->SetAlpha(1);
                $pdf->SetTextColor(0, 0, 0);
            }
        }
        return $pdf;
    }
}
