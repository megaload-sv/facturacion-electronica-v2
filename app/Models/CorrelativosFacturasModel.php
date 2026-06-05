<?php

namespace App\Models;

use CodeIgniter\Model;

class CorrelativosFacturasModel extends Model
{

    protected $table = 'correlativosfacturas';
    protected $primaryKey = 'idCorrelativoFactura';
    protected $allowedFields = ['idSucursal', 'codigoTipoDocumento', 'correlativo', 'anio'];

    public function setNextCorrelativo($codigoTipoDocumento, $codigoPuntoVenta, $anio)
    {

        $this->db->transStart();

        // Obtener la sucursal a partir del punto de venta
        $pvRow = $this->db->table('puntosdeventa')
            ->select('idSucursal')
            ->where('codigoPuntoVenta', $codigoPuntoVenta)
            ->get()
            ->getRow();

        $idSucursal = $pvRow->idSucursal;

        // Obtener el último correlativo para el tipo de documento y punto de venta especificados
        $builder = $this->builder();
        $result = $builder->select('cf.correlativo, cf.idSucursal, cf.idCorrelativoFactura, cf.anio')
            ->from($this->table . ' cf')
            ->where('cf.idSucursal', $idSucursal)
            ->where('cf.codigoTipoDocumento', $codigoTipoDocumento)
            ->where('cf.anio', $anio)
            ->orderBy('cf.correlativo', 'DESC')
            ->get()
            ->getRow();

        // Si existe un correlativo, incrementar; si no, comenzar en 1
        $nextCorrelativo = $result ? $result->correlativo + 1 : 1;

        if ($result) {
            $this->update($result->idCorrelativoFactura, ['correlativo' => $nextCorrelativo]);
        } else {
            // Guardar el nuevo correlativo en la base de datos
            $data = [
                'idSucursal' => $idSucursal,
                'codigoTipoDocumento' => $codigoTipoDocumento,
                'correlativo' => $nextCorrelativo,
                'anio' => $anio
            ];

            // Guardar en la tabla
            $this->save($data);
        }

        $this->db->transComplete();

        return $nextCorrelativo;
    }

}