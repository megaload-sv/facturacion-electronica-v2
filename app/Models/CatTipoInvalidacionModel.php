<?php

namespace App\Models;

use CodeIgniter\Model;

class CatTipoInvalidacionModel extends Model
{
    protected $table = 'cattipoinvalidacion';
    protected $primaryKey = 'codigo';
    protected $allowedFields = ['valores'];
}