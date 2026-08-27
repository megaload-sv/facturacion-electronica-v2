<?php

namespace App\Models;

use CodeIgniter\Model;

class SellosdteModel extends Model
{
    protected $table = 'sellosdte';
    protected $primaryKey = 'idsellosDTE';

    protected $allowedFields = ['identicadorNumInterno', 'codigoGeneracion', 'correlativoFactCRM', 'numeroControlMH', 'numberInvoiceCRM', 'codigoTipoDTE', 'version', 'fechaFactura', 'jsonDTE', 'firmaFactura', 'fechaFirma', 'usuarioFirma', 'usuarioSello', 'fechaSello', 'errorMH', 'jsonAnulacion', 'firmaAnulacion', 'selloAnulacion', 'fechaAnulacion', 'usuarioAnulacion', 'solicitanteAnulacion', 'anulacionMotivo', 'anulacionTipoDoc', 'anulacionNumDoc', 'anulacionTelefono', 'anulacionEmail', 'anulacionTipo', 'usuarioImprime', 'fechaImprime', 'codigoPuntoVenta', 'facturaPDF', 'idEstadoDTE', 'correoEnviado',
        'fechaCorreoEnviado',
        'errorCorreo',];
    protected $returnType = 'array';

    public function getDTEByCodigoGeneracion($factura)
    {

        $db = \Config\Database::connect();

        // Obtener las facturas procesadas
        $builder = $db->table('sellosdte dte')
            ->select('tdoc.valores as TipoDTE,
                       dte.identicadorNumInterno,
                       concat(emp.nombreComercial, " | ", suc.nombre, " | ", pv.nombre) as Empresa,
                       numeroControlMH,
                       fechaFactura,
                       dte.idEstadoDTE')
            ->join('cattipodocumento tdoc', 'dte.codigoTipoDTE = tdoc.codigo')
            ->join('puntosdeventa pv', 'dte.codigoPuntoVenta = pv.codigoPuntoVenta')
            ->join('sucursales suc', 'suc.idSucursal = pv.idSucursal')
            ->join('dteempresas emp', 'suc.codigoEmpresa = emp.codigoEmpresa')
            ->where('dte.codigoGeneracion', $factura);

        return $builder->get()->getResultArray();

    }

    public function getDTE(): array
    {

        $db = \Config\Database::connect();

        // Obtener las facturas procesadas
        $builder = $db->table('sellosdte dte')
            ->select('tdoc.valores as TipoDTE,
                       dte.identicadorNumInterno,
                       dte.codigoGeneracion,
                       concat(emp.nombreComercial, " | ", suc.nombre, " | ", pv.nombre) as Empresa,
                       coalesce(
                           nullif(nullif(json_unquote(json_extract(dte.jsonDTE, "$.receptor.nombre")), "null"), ""),
                           "Sin nombre de receptor"
                       ) as company,
                       numeroControlMH,
                       fechaFactura,
                       dte.idEstadoDTE')
            ->join('cattipodocumento tdoc', 'dte.codigoTipoDTE = tdoc.codigo')
            ->join('puntosdeventa pv', 'dte.codigoPuntoVenta = pv.codigoPuntoVenta')
            ->join('sucursales suc', 'suc.idSucursal = pv.idSucursal')
            ->join('dteempresas emp', 'suc.codigoEmpresa = emp.codigoEmpresa')
            ->where('dte.idEstadoDTE = 2');

        $rsData = $builder->get()->getResultArray();

        $data['data'] = $rsData;
        $data['total'] = $this->totalDTE();

        return $data;
    }

    public function obtenerErrorMH($codigoGeneracion)
    {

        $builder = $this->db->table($this->table);

        $builder->select("
            errorMH, 
            JSON_UNQUOTE(JSON_EXTRACT(errorMH, '$.estado')) as estado, 
            JSON_UNQUOTE(JSON_EXTRACT(errorMH, '$.observaciones')) as observaciones, 
            JSON_UNQUOTE(JSON_EXTRACT(errorMH, '$.descripcionMsg')) as descripcionMsg
        ");

        $builder->where('codigoGeneracion', $codigoGeneracion);
        $query = $builder->get();
        return $query->getRowArray();
    }

    public function getDTEArchivo(): array
    {

        $db = \Config\Database::connect();

        // Obtener las facturas procesadas
        $builder = $db->table('sellosdte dte')
            ->select('tdoc.valores as TipoDTE,
                       dte.identicadorNumInterno,
                       concat(year(fechaFactura), "/", correlativoFactCRM) as numeroCRM,
                       dte.codigoGeneracion,
                       concat(emp.nombreComercial, " | ", suc.nombre, " | ", pv.nombre) as Empresa,
                       coalesce(
                           nullif(json_unquote(json_extract(dte.jsonDTE, "$.receptor.nombre")), ""),
                           "Sin nombre de receptor"
                       ) as company,
                       numeroControlMH,
                       fechaFactura,
                       dte.idEstadoDTE,
                       case when dte.idEstadoDTE = 1 then \'Transmitido\' when dte.idEstadoDTE = 4 then \'Anulado\' end as estadoNombre,
                       dte.correoEnviado,
                       dte.fechaCorreoEnviado,
                       dte.errorCorreo',
            )
            ->join('cattipodocumento tdoc', 'dte.codigoTipoDTE = tdoc.codigo')
            ->join('puntosdeventa pv', 'dte.codigoPuntoVenta = pv.codigoPuntoVenta')
            ->join('sucursales suc', 'suc.idSucursal = pv.idSucursal')
            ->join('dteempresas emp', 'suc.codigoEmpresa = emp.codigoEmpresa')
            ->whereIn('dte.idEstadoDTE', [1, 4]);

        $rsData = $builder->get()->getResultArray();

        $data['data'] = $rsData;
        $data['total'] = $this->totalDTEArchivo();

        return $data;
    }

    public function totalDTE()
    {

        $db = \Config\Database::connect();

        // Obtener las facturas procesadas
        $builder = $db->table('sellosdte dte')
            ->select('tdoc.valores as TipoDTE,
                       dte.identicadorNumInterno,
                       dte.codigoGeneracion,
                       concat(emp.nombreComercial, " | ", suc.nombre, " | ", pv.nombre) as Empresa,
                       numeroControlMH,
                       fechaFactura,
                       dte.idEstadoDTE')
            ->join('cattipodocumento tdoc', 'dte.codigoTipoDTE = tdoc.codigo')
            ->join('puntosdeventa pv', 'dte.codigoPuntoVenta = pv.codigoPuntoVenta')
            ->join('sucursales suc', 'suc.idSucursal = pv.idSucursal')
            ->join('dteempresas emp', 'suc.codigoEmpresa = emp.codigoEmpresa')
            ->where('dte.idEstadoDTE = 2');

        $rsData = $builder->get()->getResultArray();

        return count($rsData);

    }

    public function totalDTEArchivo(): int
    {
        return $this->db->table('sellosdte')
            ->whereIn('idEstadoDTE', [1, 4])
            ->countAllResults();
    }

    public function getByCodigoGeneracion($codigoGeneracion)
    {
        return $this->where('codigoGeneracion', $codigoGeneracion)->findAll();
    }

    public function getJsonDTEByCodigoGeneracion($codigoGeneracion)
    {
        return $this->asArray()
            ->where('codigoGeneracion', $codigoGeneracion)
            ->first();
    }

    public function getDteParaReemplazar($codigoGeneracion)
    {
        $db = \Config\Database::connect();

        $sql = "SELECT dte.codigoGeneracion
                    FROM sellosdte dte
                    INNER JOIN (
                        SELECT codigoTipoDTE,
                               errorMH ->> '$.fhProcesamiento' AS fechaProcesamiento,
                               jsonDTE ->> '$.receptor.nombre' AS nombreReceptor
                        FROM sellosdte
                        WHERE codigoGeneracion = ?
                    ) inv ON dte.codigoTipoDTE = inv.codigoTipoDTE
                          AND dte.errorMH ->> '$.fhProcesamiento' > inv.fechaProcesamiento
                          AND dte.jsonDTE ->> '$.receptor.nombre' = inv.nombreReceptor
                ";

        $query = $db->query($sql, [$codigoGeneracion]);

        return $query->getResultArray();
    }

    public function getInvalidarDTEInfo($codigoGeneracion)
    {

        $db = \Config\Database::connect();

        $sql = "select codigoTipoDTE,
                       errorMH ->> '$.fhProcesamiento'        as fechaProcesamiento,
                       jsonDTE ->> '$.receptor.numDocumento'  as numDocumentoReceptor,
                       jsonDTE ->> '$.receptor.nombre'        as nombreReceptor,
                       jsonDTE ->> '$.receptor.tipoDocumento' as tipoDocumentoReceptor,
                       codigoGeneracion
                from sellosdte
                where codigoGeneracion = ?;";

        $query = $db->query($sql, [$codigoGeneracion]);

        return $query->getResultArray();

    }

    public function getInforDteToInvalidate($codigoGeneracion)
    {
        $db = \Config\Database::connect();

        $sql = "select dte.idsellosDTE,
                    dte.codigoTipoDTE,
                   codigoGeneracion,
                   dte.jsonDTE ->> '$.emisor.nit'                      as nitEmisor,
                   dte.jsonDTE ->> '$.emisor.nombre'                   as nombreEmisor,
                   dte.jsonDTE ->> '$.emisor.tipoEstablecimiento'      as tipoEstablecimientoEmidor,
                   dte.jsonDTE ->> '$.identificacion.codigoGeneracion' as codigoGeneracion,
                   dte.jsonDTE ->> '$.emisor.codEstableMH'             as codEstableMHEmisor,
                   dte.jsonDTE ->> '$.emisor.codEstable'               as codEstableEmisor,
                   dte.jsonDTE ->> '$.emisor.codPuntoVentaMH'          as codPuntoVentaMHEmisor,
                   dte.jsonDTE ->> '$.emisor.codPuntoVenta'            as codPuntoVentaEmisor,
                   dte.jsonDTE ->> '$.emisor.telefono'                 as telefonoEmisor,
                   dte.jsonDTE ->> '$.emisor.correo'                   as correoEmisor,
                   dte.jsonDTE ->> '$.identificacion.fecEmi'           as fechaEmision,
                   dte.jsonDTE ->> '$.identificacion.tipoDte'          as tipoDte,
                   dte.errorMH ->> '$.selloRecibido'                   as selloRecibido,
                   dte.jsonDTe ->> '$.identificacion.numeroControl'    as numeroControl,
                   dte.jsonDTe ->> '$.resumen.totalIva'                as totalIva,
                   case
                       when dte.codigoTipoDTE = '03' || dte.codigoTipoDTE = '11' || dte.codigoTipoDTE = '01' then
                           '36' end                                    as tipoDocumentoReceptor,
                   case
                       when dte.codigoTipoDTE = '03' then
                           dte.jsonDTE ->> '$.receptor.nit'
                       when dte.codigoTipoDTE = '11' || dte.codigoTipoDTE = '01' then
                           dte.jsonDTE ->> '$.receptor.numDocumento' end as numDocumentoReceptor,
                   jsonDTE ->> '$.receptor.nombre'                     as nombreReceptor,
                   jsonDTE ->> '$.receptor.correo'                     as correoReceptor,
                   jsonDTE ->> '$.receptor.telefono'                   as telefonoReceptor
            from sellosdte dte
            where dte.codigoGeneracion = ?;";

        $query = $db->query($sql, [$codigoGeneracion]);

        return $query->getRowArray();
    }

    public function getCorreoReceptor($codigoGeneracion)
    {

        $db = \Config\Database::connect();

        $sql = "select dte.jsonDTE ->> '$.receptor.nombre'                     as nombreReceptor,
                   dte.jsonDTE ->> '$.receptor.correo'                     as correoReceptor
            from sellosdte dte
            where dte.codigoGeneracion = ?;";

        $query = $db->query($sql, [$codigoGeneracion]);

        return $query->getRowArray();

    }


}

