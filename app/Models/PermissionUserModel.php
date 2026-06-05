<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionUserModel extends Model
{
    protected $table = 'permission_user';  // Nombre de la tabla
    protected $primaryKey = 'id';          // Clave primaria de la tabla (si existe)

    // Los campos que se pueden insertar o actualizar
    protected $allowedFields = ['user_id', 'permission_id'];

    // Activar timestamps si tienes columnas como created_at o updated_at
    // protected $useTimestamps = true;

    // Si las columnas `created_at` y `updated_at` son automáticas
    // Puedes personalizarlas si tu tabla las tiene
     protected $createdField = 'created_at';
     protected $updatedField = 'updated_at';
}