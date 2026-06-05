<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table = 'roles';  // Asegúrate que tu tabla de roles se llame así
    protected $primaryKey = 'id';
    protected $allowedFields = ['role_name', 'description', 'created_at', 'updated_at'];
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
