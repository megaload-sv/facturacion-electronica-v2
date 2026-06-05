<?php

namespace App\Models;

use CodeIgniter\Model;

class CatTipoIdentificacionReceptorModel extends Model
{
    protected $table = 'cattipodocidentificareceptor';
    protected $primaryKey = 'codigo';
    protected $allowedFields = ['valores'];
}