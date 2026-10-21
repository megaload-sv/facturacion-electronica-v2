# Reglas de invalidación

El servidor evalúa los plazos con `App\Services\InvalidacionPlazos` antes de firmar el evento.
La vista consulta ese mismo servicio mediante `GET /dte-invalidar/{codigo}`. El POST vuelve
a evaluar con los datos guardados del DTE y la hora de El Salvador; no confía en fechas
ni resultados enviados por el navegador.

## Instalación

Ejecutar `php spark migrate` en cada ambiente. Crea tres tablas y carga reglas 2.0
y el calendario tributario oficial de 2026. No modifica DTE ni envía eventos.

Fuente de reglas: [Normativa oficial](https://factura.gob.sv/normativa/), versión 2.0,
25/05/2026, cuadro 6 y apartados 13.1.1–13.1.1.1, pp. 28–29.
Fuente de calendario: [MH 2026](https://www.mh.gob.sv/wp-content/uploads/2025/12/Calendario-Tributario-2026.pdf).

## Cambiar reglas

`dte_invalidacion_reglas` guarda una fila por tipo de DTE, motivo y condición:

- `tipo_dte`: código MH de dos caracteres.
- `motivo`: `*` para todos, o `1`, `2`, `3`. Una regla específica prevalece sobre `*`.
- `condicion`: `general` o una clasificación verificada de la operación.
- `vigente_desde` / `vigente_hasta`: fechas inclusivas respecto del día de evaluación.
  La fecha de publicación 25/05/2026 se usa para esta implementación 2.0. Un cambio
  futuro con transición especial requiere definir expresamente su aplicación.
- `transmision_calculo` y `evento_calculo`: `dias`, `meses`, `habiles_mes_siguiente`.
- `transmision_cantidad` y `evento_cantidad`: cantidad asociada a cada cálculo.
- `fecha_evento_origen`: `actual` o `generacion`.
- `version` y `referencia`: identificación y sustento de la regla.

Para una nueva versión, cerrar la anterior el día previo y agregar la nueva en una
transacción. No borrar reglas históricas. Reglas faltantes o superpuestas bloquean
la invalidación con un mensaje. Los meses se calculan por calendario, ajustando al
último día del mes cuando no existe el día original; el cierre es 23:59:59.
Cambiar cantidades y vigencias no necesita cambiar código. Un tipo de cálculo o
una condición automática nueva sí necesita implementación y pruebas.

## Calendario

`dte_calendario` requiere una fila por fecha, incluyendo fines de semana y asuetos,
con `habil` igual a 0 o 1 y una descripción del fundamento. El décimo día hábil se
cuenta en el mes siguiente al sello. Se carga 2026 según la fuente oficial; mantener
actualizados los cambios extraordinarios y cargar cada año nuevo antes de utilizarlo.
No se inventa el calendario de 2027. Una fecha ausente bloquea el cálculo hasta que
se complete. Un DTE sellado en diciembre necesita calendario de enero del año siguiente.

## Excepción farmacéutica

Las reglas de 24 meses solo aplican a FE y FEXE. Para usarlas, registrar el UUID
en `dte_invalidacion_contextos`, con `condicion = farmaceutica_perecedera` y
`justificacion` verificable. Esa clasificación debe ser validada por el responsable
tributario: no se deduce del texto de productos ni se acepta desde el formulario.
Sin clasificación se aplica la regla general. No hay pantalla administrativa en esta etapa.

## Verificación

`php vendor/bin/phpunit tests/unit/InvalidacionPlazosTest.php --no-coverage`

Las pruebas usan SQLite en memoria, sin modificar la base del facturador ni enviar a MH.
