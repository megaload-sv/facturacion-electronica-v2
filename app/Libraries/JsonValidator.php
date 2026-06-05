<?php

namespace App\Libraries;

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

class JsonValidator
{
    public function validateJson($tipoDocumento, $arregloToValidate)
    {
        // Cargar el esquema según el tipo de documento
        if ($tipoDocumento === "01") {
            $schemaPath = 'schemas/fe-fc-v1.json'; // Ruta al esquema
        } else {
            throw new \InvalidArgumentException("Tipo de documento no válido.");
        }

        $schema = json_decode(file_get_contents($schemaPath));

        // Crear una instancia del validador
        $validator = new Validator();


        // Validar el JSON contra el esquema
        $validator->validate($arregloToValidate, $schema);

        // Verificar si es válido
        if ($validator->isValid()) {
            return ['valid' => true];
        } else {
            $errors = [];
            foreach ($validator->getErrors() as $error) {
                $errors[] = sprintf("[%s] %s", $error['property'], $error['message']);
            }
            return ['valid' => false, 'errors' => $errors];
        }
    }
}