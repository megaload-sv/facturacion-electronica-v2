<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class InvalidacionPlazos
{
    public function __construct(private $db = null)
    {
        $this->db ??= \Config\Database::connect();
    }

    public function evaluar(array $dte, string $motivo, ?DateTimeImmutable $ahora = null): array
    {
        $ahora = ($ahora ?? new DateTimeImmutable('now', new DateTimeZone('America/El_Salvador')))
            ->setTimezone(new DateTimeZone('America/El_Salvador'));
        try {
            if (!in_array($motivo, ['1', '2', '3'], true)) {
                throw new RuntimeException('Seleccione un motivo de invalidación válido.');
            }
            if (empty($dte['selloRecibido']) || $dte['selloRecibido'] === 'null') {
                throw new RuntimeException('El documento no tiene sello de recepción.');
            }
            // La vigencia corresponde a la fecha de evaluación, no a la emisión del DTE.
            $contexto = $this->db->table('dte_invalidacion_contextos')
                ->where('codigo_generacion', $dte['codigoGeneracion'] ?? '')->get()->getRowArray();
            $condicion = $contexto['condicion'] ?? 'general';
            $reglas = $this->db->table('dte_invalidacion_reglas')
                ->where('tipo_dte', $dte['tipoDte'])->whereIn('motivo', ['*', $motivo])
                ->where('condicion', $condicion)
                ->where('vigente_desde <=', $ahora->format('Y-m-d'))
                ->groupStart()->where('vigente_hasta', null)->orWhere('vigente_hasta >=', $ahora->format('Y-m-d'))->groupEnd()
                ->get()->getResultArray();
            $especificas = array_values(array_filter($reglas, fn ($r) => $r['motivo'] === $motivo));
            $reglas = $especificas ?: array_values(array_filter($reglas, fn ($r) => $r['motivo'] === '*'));
            if (count($reglas) !== 1) {
                throw new RuntimeException('No existe una regla única vigente para este documento y motivo. Revise la configuración.');
            }
            $regla = $reglas[0];
            $generacion = self::fecha($dte['fechaEmision'] ?? '');
            $recepcion = self::fecha($dte['fechaProcesamiento'] ?? '', true);
            if ($generacion > $ahora || $recepcion > $ahora) {
                throw new RuntimeException('Las fechas del documento son posteriores a la fecha actual.');
            }
            $evento = match ($regla['fecha_evento_origen']) {
                'actual' => $ahora->setTime(0, 0),
                'generacion' => $generacion,
                default => throw new RuntimeException('Origen de fecha del evento no soportado.'),
            };
            $limite = $this->limite($recepcion, $regla['transmision_calculo'], (int) $regla['transmision_cantidad']);
            $limiteEvento = $this->limite($generacion, $regla['evento_calculo'], (int) $regla['evento_cantidad']);
            $permitido = $ahora <= $limite && $evento >= $generacion && $evento <= $limiteEvento;
            return [
                'permitido' => $permitido, 'fechaEvento' => $evento->format('Y-m-d'),
                'limiteTransmision' => $limite->format('Y-m-d H:i:s'),
                'limiteFechaEvento' => $limiteEvento->format('Y-m-d'),
                'reglaId' => (int) $regla['id'], 'version' => $regla['version'],
                'message' => $permitido ? 'Plazo permitido. Transmitir hasta ' . $limite->format('d/m/Y H:i:s') . '.'
                    : 'No es posible invalidar: venció el plazo de transmisión o de la fecha del evento.',
            ];
        } catch (RuntimeException $e) {
            return ['permitido' => false, 'message' => $e->getMessage()];
        }
    }

    private static function fecha(string $valor, bool $conHora = false): DateTimeImmutable
    {
        // Hacienda devuelve fhProcesamiento como dd/mm/YYYY HH:mm:ss.
        foreach ($conHora ? ['!d/m/Y H:i:s', '!Y-m-d H:i:s', '!Y-m-d\TH:i:s'] : ['!Y-m-d'] as $formato) {
            $fecha = DateTimeImmutable::createFromFormat($formato, $valor, new DateTimeZone('America/El_Salvador'));
            $errores = DateTimeImmutable::getLastErrors();
            if ($fecha !== false && ($errores === false || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))) {
                return $fecha;
            }
        }
        throw new RuntimeException('Falta una fecha válida de generación o recepción del DTE.');
    }

    public function limite(DateTimeImmutable $base, string $calculo, int $cantidad): DateTimeImmutable
    {
        if ($cantidad < 0 || $cantidad > 366) {
            throw new RuntimeException('Cantidad de plazo fuera del rango soportado.');
        }
        if ($calculo === 'dias') {
            return $base->modify("+{$cantidad} days")->setTime(23, 59, 59);
        }
        if ($calculo === 'meses') {
            $mes = $base->modify('first day of this month')->modify("+{$cantidad} months");
            return $mes->setDate((int) $mes->format('Y'), (int) $mes->format('m'),
                min((int) $base->format('d'), (int) $mes->format('t')))->setTime(23, 59, 59);
        }
        if ($calculo === 'habiles_mes_siguiente' && $cantidad > 0) {
            $dia = $base->modify('first day of next month')->setTime(0, 0);
            $mes = $dia->format('Y-m');
            $contados = 0;
            while ($dia->format('Y-m') === $mes) {
                $registro = $this->db->table('dte_calendario')->where('fecha', $dia->format('Y-m-d'))->get()->getRowArray();
                if (!$registro) {
                    throw new RuntimeException('Debe completar el calendario de días hábiles para ' . $dia->format('Y-m-d') . '.');
                }
                if ((int) $registro['habil'] === 1 && ++$contados === $cantidad) {
                    return $dia->setTime(23, 59, 59);
                }
                $dia = $dia->modify('+1 day');
            }
        }
        throw new RuntimeException('Tipo de cálculo o cantidad de plazo no soportado.');
    }
}
