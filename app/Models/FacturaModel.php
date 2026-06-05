<?php

namespace App\Models;

use CodeIgniter\Model;

class FacturaModel extends Model
{

    private $dbase;

    public function __construct()
    {
        $this->dbase = \Config\Database::connect();
    }

    public function get_informacion_general($codigoPuntoVenta)
    {
        $builder = $this->dbase->table('dteempresas emp')
            ->select("emp.nit,
                               emp.nrc,
                               emp.nombreRazonSocial,
                               emp.codigoActividad,
                               emp.descripcionActividad,
                               emp.nombreComercial,
                               emp.telefono,
                               emp.correo,
                               suc.codigoTipoEstablecimiento,
                               suc.codigoDepartamento,
                               de.valores as nombreDepartamento,
                               suc.codigoMunicipio,
                               mu.valores as nombreMunicipio,
                               di.codigo  as codigoDistrito,
                               di.valores as nombreDistrito,
                               suc.direccion,
                               suc.codigoMH,
                               suc.idSucursal,
                               pv.codigoPuntoVenta,
                               pv.codigoPuntoVentaMH")
            ->join('sucursales suc', 'emp.codigoEmpresa = suc.codigoEmpresa')
            ->join('puntosdeventa pv', 'suc.idSucursal = pv.idSucursal')
            ->join('seguridad seg', 'emp.codigoEmpresa = seg.codigoEmpresa')
            ->join('catmunicipio mu', 'mu.codigo = suc.codigoMunicipio and mu.codigodepartamento = suc.codigoDepartamento')
            ->join('catdistrito di', 'di.id = mu.idDistrito')
            ->join('catdepartamento de', 'de.codigo = suc.codigoDepartamento')
            ->where('pv.codigoPuntoVenta', $codigoPuntoVenta);

        $query = $builder->get();

        return $query->getRow();

    }

    public function get_correlativo_por_tipo_documento($tipoDocumento, $anio)
    {

        $builder = $this->dbase->table('dteempresas emp')
            ->select("concat('DTE-', codigoTipoDocumento, '-', suc.codigoMH, pv.codigoPuntoVentaMH, '-', LPAD(cor.correlativo,15,'0')) corre")
            ->join('sucursales suc', 'emp.codigoEmpresa = suc.codigoEmpresa')
            ->join('puntosdeventa pv', 'suc.idSucursal = pv.idSucursal')
            ->join('correlativosfacturas cor', 'suc.idSucursal = cor.idSucursal')
            ->where('cor.codigoTipoDocumento', $tipoDocumento)
            ->where('cor.anio', $anio);
        $query = $builder->get();
        //echo $this->dbase->getLastQuery();
        return $query->getRow();

    }

    public function getDistritoByCodeMunicipioCodeDepartamento($codigoMunicipio, $codigoDepartamento)
    {
        $builder = $this->dbase->table('catdistrito di')
            ->select('di.id, di.codigo, di.valores')
            ->join('catmunicipio mu', 'di.id = mu.idDistrito')
            ->where('mu.codigo', $codigoMunicipio)
            ->where('mu.codigodepartamento', $codigoDepartamento);
        $query = $builder->get();
        return $query->getRow();
    }

}