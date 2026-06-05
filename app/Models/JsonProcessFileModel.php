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
        'register',
        'nit',
        'numero_control',
        'generation_code',
        'issue_date',
        'issuer_name',
        'gravadas',
        'exentas',
        'total_pagar',
        'iva_13',
        'iva_1',
        'fovial',
        'cotrans',
        'sello_recibido',
        'moved_path',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

}
