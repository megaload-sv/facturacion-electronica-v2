<?php

namespace App\Libraries;

final class InvalidacionDiagnostico
{
    public static function comparar(array $evento, string $firma): array
    {
        $partes = explode('.', $firma);
        $firmado = null;
        if (count($partes) === 3) {
            $payload = strtr($partes[1], '-_', '+/');
            $payload = str_pad($payload, (int) (ceil(strlen($payload) / 4) * 4), '=');
            $json = base64_decode($payload, true);
            $decoded = $json === false ? null : json_decode($json, true);
            if (is_array($decoded)) {
                $firmado = $decoded;
            }
        }

        return [
            'generado' => self::campos($evento),
            'firmado' => $firmado === null ? null : self::campos($firmado),
            'firmaDecodificable' => $firmado !== null,
            // Esta comparación inspecciona el payload; no verifica la firma criptográfica.
            'camposCoinciden' => $firmado === null ? null : self::campos($evento) === self::campos($firmado),
        ];
    }

    private static function campos(array $evento): array
    {
        $campos = [];
        foreach ([
            'identificacion' => ['version', 'ambiente', 'codigoGeneracion', 'fecEmi', 'horEmi'],
            'documento' => ['tipoDte', 'codigoGeneracion', 'numeroControl', 'fecEmi', 'codigoGeneracionR'],
            'motivo' => ['tipoAnulacion'],
        ] as $seccion => $nombres) {
            foreach ($nombres as $nombre) {
                $valor = $evento[$seccion][$nombre] ?? null;
                $campos[$seccion][$nombre] = is_scalar($valor) ? $valor : null;
            }
        }
        return $campos;
    }
}
