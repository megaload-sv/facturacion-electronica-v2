<?php

namespace App\Models;

use CodeIgniter\Model;

class JsonProcessFileModel extends Model
{
    protected $table = 'json_process_files';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'group_id',
        'file_name',
        'numero_control',
        'generation_code',
        'issue_date',
        'issuer_name',
        'receiver_name',
        'total_pagar',
        'iva_13',
        'fovial',
        'cotrans',
        'estado_hacienda',
        'codigo_msg_hacienda',
        'descripcion_msg_hacienda',
        'observaciones_hacienda',
        'sello_recibido',
        'empleado',
        'no_unico',
        'observacion',
        'sucursal',
        'moved_path',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

}
