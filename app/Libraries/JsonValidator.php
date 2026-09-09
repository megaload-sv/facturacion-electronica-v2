<?php

namespace App\Libraries;

use Opis\JsonSchema\CompliantValidator;
use Opis\JsonSchema\Errors\ValidationError;

class JsonValidator
{
    private const SCHEMAS = [
        '01' => 'fe-fc-v1.json',
        '03' => 'fe-ccf-v3.json',
        '05' => 'fe-nc-v3.json',
        '06' => 'fe-nd-v3.json',
        '11' => 'fe-fex-v1.json',
        '14' => 'fe-fse-v1.json',
    ];

    private array $schemas = [];
    private ?CompliantValidator $validator = null;

    /** Solo valida el contrato ERP y los datos necesarios para preparar el DTE. */
    public function validateErpResponse($response): array
    {
        $errors = [];
        if (!$response instanceof \stdClass) {
            return $this->result(['La respuesta del ERP debe ser un objeto JSON.']);
        }
        if (!property_exists($response, 'error') || !is_bool($response->error)) {
            $errors[] = 'El campo error de la respuesta del ERP debe ser booleano.';
        } elseif ($response->error) {
            $errors[] = 'El ERP indicó un error al obtener el documento.';
            if (isset($response->message) && is_string($response->message) && trim($response->message) !== '') {
                $errors[] = $response->message;
            }
        }
        foreach (['datainfo', 'data'] as $node) {
            if (!(($response->$node ?? null) instanceof \stdClass)) {
                $errors[] = "Falta el objeto {$node} en la respuesta del ERP.";
            }
        }
        if ($errors) {
            return $this->result($errors);
        }
        foreach (['identicadorNumInterno', 'correlativoFactCRM', 'codigoGeneracion', 'codigoTipoDTE', 'version', 'fechaFactura', 'codigoPuntoVenta', 'codPais'] as $field) {
            $value = $response->datainfo->$field ?? null;
            if ((!is_string($value) && !is_int($value)) || trim((string) $value) === '') {
                $errors[] = "El campo datainfo.{$field} es requerido y debe ser texto o entero.";
            }
        }
        $identificacion = $response->data->identificacion ?? null;
        if (!$identificacion instanceof \stdClass) {
            $errors[] = 'Falta el objeto data.identificacion.';
        } else {
            $tipo = $identificacion->tipoDte ?? null;
            if (!is_string($tipo) || !isset(self::SCHEMAS[$tipo])) {
                $errors[] = 'data.identificacion.tipoDte debe ser uno de: ' . implode(', ', array_keys(self::SCHEMAS)) . '.';
            }
            foreach (['codigoTipoDTE' => 'tipoDte', 'version' => 'version', 'codigoGeneracion' => 'codigoGeneracion'] as $metadata => $field) {
                $meta = $response->datainfo->$metadata ?? null;
                $value = $identificacion->$field ?? null;
                if (is_scalar($meta) && is_scalar($value)) {
                    $meta = $metadata === 'codigoTipoDTE' ? str_pad((string) $meta, 2, '0', STR_PAD_LEFT) : (string) $meta;
                    if ($meta !== (string) $value) {
                        $errors[] = "datainfo.{$metadata} no coincide con data.identificacion.{$field}.";
                    }
                }
            }
        }
        // El emisor se completa con configuración local. Las reglas de receptor,
        // cuerpoDocumento y resumen se validan exclusivamente con el esquema.
        if (isset($response->data->emisor)) {
            if (!$response->data->emisor instanceof \stdClass) {
                $errors[] = 'data.emisor debe ser un objeto.';
            } elseif (isset($response->data->emisor->direccion) && !$response->data->emisor->direccion instanceof \stdClass) {
                $errors[] = 'data.emisor.direccion debe ser un objeto.';
            }
        }
        return $this->result($errors);
    }

    public function getRecipientNode(string $tipoDocumento): string
    {
        return isset($this->schema($tipoDocumento)->properties->sujetoExcluido) ? 'sujetoExcluido' : 'receptor';
    }

    /** Incorpora solo campos de configuración definidos para el emisor del tipo. */
    public function completeEmisor(string $tipoDocumento, \stdClass $emisor, array $configuration): void
    {
        $properties = $this->schema($tipoDocumento)->properties->emisor->properties;
        foreach ($configuration as $field => $value) {
            if (property_exists($properties, $field)) {
                $emisor->$field = $value;
            }
        }
    }

    /** Recibe el DTE completo como objeto, antes de firmar; no modifica sus datos. */
    public function validateJson(string $tipoDocumento, \stdClass $documento): array
    {
        if (!isset(self::SCHEMAS[$tipoDocumento])) {
            return $this->result(['[data.identificacion.tipoDte] Tipo de documento no soportado: ' . $tipoDocumento]);
        }
        // Solo JSON Schema estándar: sin defaults, filtros ni transformaciones.
        // Se conservan todos los errores, incluidos los de distintas propiedades.
        $this->validator ??= new CompliantValidator(null, PHP_INT_MAX, false);
        $validation = $this->validator->validate($documento, $this->schema($tipoDocumento));
        $errors = $validation->isValid() ? [] : $this->collectErrors($validation->error());
        return $this->result(array_values(array_unique($errors)));
    }

    private function schema(string $tipoDocumento): \stdClass
    {
        if (!isset(self::SCHEMAS[$tipoDocumento])) {
            throw new \InvalidArgumentException('Tipo de documento no soportado.');
        }
        if (isset($this->schemas[$tipoDocumento])) {
            return $this->schemas[$tipoDocumento];
        }
        $path = WRITEPATH . 'schemas/' . self::SCHEMAS[$tipoDocumento];
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException('No se puede leer el esquema para el DTE ' . $tipoDocumento . '.');
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException('No se pudo cargar el esquema para el DTE ' . $tipoDocumento . '.');
        }
        $schema = json_decode($contents, false, 512, JSON_THROW_ON_ERROR);
        if (!$schema instanceof \stdClass) {
            throw new \RuntimeException('El esquema del DTE no contiene un objeto JSON.');
        }
        return $this->schemas[$tipoDocumento] = $schema;
    }

    private function result(array $errors): array
    {
        return [
            'valid' => !$errors,
            'errors' => $errors,
            'error' => (bool) $errors,
            'message' => $errors ? 'Revisa los datos del documento.' : 'Validación exitosa.',
            'detalle' => $errors,
        ];
    }

    /** Desglosa los errores agrupados de Opis en mensajes por campo. */
    private function collectErrors(ValidationError $error): array
    {
        if ($error->subErrors()) {
            $messages = [];
            foreach ($error->subErrors() as $child) {
                array_push($messages, ...$this->collectErrors($child));
            }
            return $messages;
        }

        $path = $error->data()->fullPath();
        $args = $error->args();
        $fields = match ($error->keyword()) {
            'required' => $args['missing'] ?? [],
            'additionalProperties' => $args['properties'] ?? [],
            default => [],
        };
        if ($fields) {
            $messages = [];
            foreach ($fields as $field) {
                // Al acumular errores, Opis puede incluir una propiedad declarada
                // cuyo contenido falló. Ya se informa su error específico.
                if ($error->keyword() === 'additionalProperties'
                    && property_exists($error->schema()->info()->data()->properties ?? new \stdClass(), $field)) {
                    continue;
                }
                $messages[] = $this->formatError($error, [...$path, $field]);
            }
            return $messages;
        }

        // JSON se decodifica en float. Valores decimales válidos como 870.10
        // pueden quedar internamente como 870.10000000000002 y provocar un
        // falso positivo de multipleOf en Opis cuando BCMath está habilitado.
        if ($error->keyword() === 'multipleOf'
            && $this->isDecimalMultiple($error->data()->value(), $error->args()['divisor'] ?? null)) {
            return [];
        }

        return [$this->formatError($error, $path)];
    }

    private function formatPath(array $segments): string
    {
        $path = 'data';
        foreach ($segments as $segment) {
            $path .= is_int($segment) ? "[{$segment}]" : '.' . $segment;
        }
        return $path;
    }

    private function isDecimalMultiple($number, $divisor): bool
    {
        if ((!is_int($number) && !is_float($number)) || (!is_int($divisor) && !is_float($divisor)) || $divisor == 0) {
            return false;
        }

        $quotient = $number / $divisor;
        if (!is_finite($quotient)) {
            return false;
        }

        // La tolerancia depende de la magnitud del cociente y únicamente
        // absorbe el error de representación IEEE-754 de la operación.
        $tolerance = PHP_FLOAT_EPSILON * max(1.0, abs($quotient)) * 8;
        return abs($quotient - round($quotient)) <= $tolerance;
    }

    private function formatError(ValidationError $error, array $segments): string
    {
        $path = $this->formatPath($segments);
        $rule = $error->keyword();
        $args = $error->args();
        $value = match ($rule) {
            'const' => $args['const'] ?? null,
            'enum' => $error->schema()->info()->data()->enum ?? [],
            'minLength', 'minItems', 'minimum', 'exclusiveMinimum' => $args['min'] ?? null,
            'maxLength', 'maxItems', 'maximum', 'exclusiveMaximum' => $args['max'] ?? null,
            'multipleOf' => $args['divisor'] ?? null,
            default => null,
        };
        $limit = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $expectedType = strtr(implode(', ', (array) ($args['expected'] ?? [])), [
            'string' => 'texto', 'integer' => 'entero', 'number' => 'número',
            'object' => 'objeto', 'array' => 'lista', 'boolean' => 'booleano',
        ]);
        $message = match ($rule) {
            'required' => 'El campo es requerido (debe estar presente).',
            'additionalProperties' => 'La propiedad no está permitida para este tipo de documento.',
            'type' => "El tipo de dato no es válido; se esperaba: {$expectedType}.",
            'const' => "El valor debe ser {$limit}.",
            'enum' => "El valor debe pertenecer a los valores permitidos: {$limit}.",
            'minLength' => "El texto debe tener al menos {$limit} caracteres.",
            'maxLength' => "El texto debe tener como máximo {$limit} caracteres.",
            'minimum' => "El número debe ser mayor o igual a {$limit}.",
            'maximum' => "El número debe ser menor o igual a {$limit}.",
            'exclusiveMinimum' => "El número debe ser mayor que {$limit}.",
            'exclusiveMaximum' => "El número debe ser menor que {$limit}.",
            'minItems' => "La lista debe contener al menos {$limit} elementos.",
            'maxItems' => "La lista debe contener como máximo {$limit} elementos.",
            'pattern' => 'El texto no cumple con el formato requerido.',
            'format' => match ($args['format'] ?? '') {
                'email' => 'El correo electrónico no tiene un formato válido.',
                'date' => 'La fecha debe ser válida y usar el formato AAAA-MM-DD.',
                'date-time' => 'La fecha y hora no tienen un formato válido.',
                default => 'El valor no cumple con el formato requerido.',
            },
            'allOf', 'anyOf', 'oneOf', 'not' => 'No cumple las condiciones del esquema para estos datos.',
            'multipleOf' => "El número debe ser múltiplo de {$limit}.",
            'uniqueItems' => 'La lista no debe contener elementos duplicados.',
            default => 'El valor no cumple la regla ' . $rule . ' del esquema.',
        };
        return "[{$path}] {$message}";
    }
}
