<?php

namespace App\Models;

use CodeIgniter\Model;

class MantenimientosModel extends Model
{
    public function obtenerUbicacionDireccion($departamento, $municipio, $distrito = null): array
    {
        // catmunicipio almacena distritos; catdistrito almacena municipios actuales.
        $builder = $this->db->table('catmunicipio dis')
            ->select('dep.valores as departamento, mun.valores as municipio, dis.valores as distrito')
            ->join('catdepartamento dep', 'dep.codigo = dis.codigodepartamento')
            ->join('catdistrito mun', 'mun.id = dis.idDistrito')
            ->where('dep.codigo', $departamento);

        if ($distrito !== null && $distrito !== '') {
            $builder->where('dis.codigo', $distrito)->where('mun.codigo', $municipio);
        } else {
            // Los DTE anteriores identifican el distrito en el campo municipio.
            $builder->where('dis.codigo', $municipio);
        }

        return $builder->get()->getRowArray() ?? [];
    }

    public function obtenerDepartamentoMunicipio(string $idDepartamento, string $idMunicipio){

        $builder = $this->db->table('catdepartamento dep')
            ->select('dep.valores as departemento, mun.valores as municipio')
            ->join('catmunicipio mun', 'dep.codigo = mun.codigodepartamento')
            ->where('dep.codigo', $idDepartamento)
            ->where('mun.codigo', $idMunicipio);

        $dataDepartementoMunicipio = $builder->get()->getResultArray();

        return ($dataDepartementoMunicipio);

    }
    public function obtenerSucursalPuntoVentaTipoEstablecimiento(string $idSucursal){

        $builder = $this->db->table('sucursales s')
            ->select('s.nombre as sucursal, pv.nombre as puntoVenta, te.valores as tipoEstablecimiento')
            ->join('puntosdeventa pv', 's.idSucursal = pv.idSucursal')
            ->join('cattipoestablecimiento te', 's.codigoTipoEstablecimiento = te.codigo')
            ->where('s.idSucursal', $idSucursal);

        $data = $builder->get()->getResultArray();

        return ($data);

    }
    public function obtenerActividadEconomica(string $codigo){
        $builder = $this->db->table('catcodigoactividadeconomica')
            ->select('valores as codigoActividadEconomica')
            ->where('codigo', $codigo);

        $data = $builder->get()->getResultArray();
        return ($data);
    }
    public function obtenerCondicionOperacion(string $codigo){
        $builder = $this->db->table('catcondicionoperacion')
            ->select('valores as condicionOperacion')
            ->where('codigo', $codigo);
        $data = $builder->get()->getResultArray();
        return ($data);
    }
    public function obtenerDocumentoReceptor(string $codigo){
        $builder = $this->db->table('cattipodocidentificareceptor')
            ->select('valores as documentoReceptor')
            ->where('codigo', $codigo);
        $data = $builder->get()->getResultArray();
        return ($data);
    }
    public function obtenerTipoDTE(string $codigo){
        $builder = $this->db->table('cattipodocumento')
            ->select('valores as tipoDTE')
            ->where('codigo', $codigo);
        $data = $builder->get()->getResultArray();
        return ($data);
    }

    public function obtenerUnidad(string $codigo){
     $builder = $this->db->table('catunidadmedida')
            ->select('valores as Unidad')
            ->where('codigo', $codigo);
        $data = $builder->get()->getResultArray();
        return ($data);
    }

}
