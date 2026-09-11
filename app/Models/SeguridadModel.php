<?php

namespace App\Models;

use CodeIgniter\Model;

class SeguridadModel extends Model
{
    protected $table = 'seguridad';

    protected $primaryKey = 'codigoEmpresa';
    protected $allowedFields = ['passwordMH', 'passwordFirma', 'tokenMH', 'fechaTokenMH', 'usuarioSolicita', 'respuestaTokenMH', 'bearerTokenMH','vidaToken','ambiente','urlFirmador', 'urlBearerToken', 'urlRecepcionDTE', 'nit', 'urlApiFacturas', 'urlGetJson', 'target'];
    protected $returnType       = 'array';


    public final function getConfigByEnvironment(): array
    {
        // Fiscal environment is independent of application debug mode.
        switch (config(\Config\Dte::class)->selectedEnvironment()) {
            case 'testing':
                $target = 2;
                $ambiente = '00';
                break;
            case 'production':
                $target = 0;
                $ambiente = '01';
                break;
            case 'development':
            default:
                $target = 1;
                $ambiente = '00';
                break;
        }

        // Trae el registro correspondiente a ese ambiente y target
        // Ajustá el orderBy si tenés más de un registro por target
        $row = $this->where('target', $target)
            ->where('ambiente', $ambiente)
            ->first();


        return $row ?? [];
    }


}
