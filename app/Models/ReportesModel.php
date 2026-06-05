<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportesModel extends Model
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function getDeclaraciones(string $fechaInicio, string $fechaFin, bool $incluirAnulados = false): array
    {
        // Ajuste: incluir rango completo del día
        $inicio = $fechaInicio . ' 00:00:00';
        $fin    = $fechaFin . ' 23:59:59';

        $ID_ESTADO_ANULADO = 4;

        $sql = "
            SELECT 
                CASE
                   WHEN codigoTipoDTE = '03' THEN 'Credito Fiscal'
                   WHEN codigoTipoDTE = '11' THEN 'Exportacion'
                   ELSE 'Consumidor Final' END                                                   AS tipo_factura,
                        JSON_UNQUOTE(JSON_EXTRACT(jsonDTE, '$.identificacion.fecEmi'))                    AS fechaEmision,
                    codigoGeneracion,
                       JSON_UNQUOTE(JSON_EXTRACT(jsonDTE, '$.identificacion.numeroControl'))             AS numeroControl,
                       JSON_UNQUOTE(JSON_EXTRACT(jsonDTE, '$.selloRecibido'))                            AS selloRecibido,
                       JSON_UNQUOTE(JSON_EXTRACT(jsonDTE, '$.receptor.nit'))                             AS nit_receptor,
                       JSON_UNQUOTE(JSON_EXTRACT(jsonDTE, '$.receptor.nrc'))                             AS nrc_receptor,
                       JSON_UNQUOTE(JSON_EXTRACT(jsonDTE, '$.receptor.nombre'))                          AS nombre_cliente,
                   case
                       when idEstadoDTE = 4 then 0
                       else JSON_UNQUOTE(JSON_EXTRACT(jsonDTE, '$.resumen.subTotalVentas')) end      AS monto_sin_iva,
                   case
                       when idEstadoDTE = 4 then 0
                       else JSON_UNQUOTE(JSON_EXTRACT(jsonDTE, '$.resumen.tributos[0].valor')) end   AS iva,
                   case
                       when idEstadoDTE = 4 then 0
                       else JSON_UNQUOTE(JSON_EXTRACT(jsonDTE, '$.resumen.totalExenta')) end         AS exento,
                   case
                       when idEstadoDTE = 4 then 0
                       else JSON_UNQUOTE(JSON_EXTRACT(jsonDTE, '$.resumen.ivaPerci1')) end           AS retencion,
                   case
                       when idEstadoDTE = 4 then 0
                       else JSON_UNQUOTE(JSON_EXTRACT(jsonDTE, '$.resumen.montoTotalOperacion')) end AS total_operacion
            FROM sellosdte
            WHERE (fechaFactura BETWEEN ? AND ?)
              AND JSON_EXTRACT(jsonDTE,'$.selloRecibido') IS NOT NULL
        ";

        $params = [$inicio, $fin];

        // Si NO incluirAnulados, los excluimos
        if (!$incluirAnulados) {
            $sql .= " AND idEstadoDTE <> ? ";
            $params[] = $ID_ESTADO_ANULADO;
        }

        $query = $this->db->query($sql, $params);

        return $query->getResultArray();
    }
}
