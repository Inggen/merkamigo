<?php

namespace App\Domain\Businesses\Actions;

use App\Domain\Businesses\Models\Business;
use App\Support\Ai\Contracts\GeneratesAssistedText;

/**
 * Convierte el texto libre de horarios (ej. "Lun-Sáb 8:00am - 6:00pm") en
 * el horario estructurado por día que usa la vitrina para mostrar
 * "Abierto ahora" — pedido del usuario: que "Horario por día" se pueda
 * autocompletar a partir de lo que ya escribió arriba, en vez de llenar
 * cada día a mano. El emprendedor sigue viendo y pudiendo ajustar el
 * resultado antes de guardar.
 */
class ParseBusinessHoursText
{
    public function __construct(
        private readonly GeneratesAssistedText $assistedText,
    ) {}

    /**
     * @return array<string, array{closed: bool, open: ?string, close: ?string}>|null null si no se pudo interpretar el texto.
     */
    public function handle(string $hoursText): ?array
    {
        $hoursText = trim($hoursText);

        if ($hoursText === '') {
            return null;
        }

        $raw = $this->assistedText->generate($this->prompt(), [
            'horario_escrito_por_el_negocio' => $hoursText,
        ]);

        return $this->parse($raw);
    }

    private function prompt(): string
    {
        $days = implode(', ', array_keys(Business::DAY_LABELS));

        return
            'Interpretas el horario de atención que un negocio colombiano escribió en texto libre '.
            '("horario_escrito_por_el_negocio") y lo conviertes al horario estructurado por día que usa '.
            'Merkamigo. Días válidos, con estas claves exactas en inglés: '.$days.'. '.
            'Si el texto agrupa varios días (ej. "Lun-Sáb", "Lunes a viernes"), aplica el mismo horario a '.
            'cada día del rango. Si el texto no menciona un día, o dice explícitamente que ese día está '.
            'cerrado, márcalo como cerrado. Las horas van en formato 24 horas "HH:MM" (ej. "8:00am" es '.
            '"08:00", "6:00pm" es "18:00"). Si el texto es ambiguo, no es un horario real, o no puedes '.
            'interpretarlo con confianza, responde con todos los días cerrados en vez de adivinar. Responde '.
            'SIEMPRE con un único objeto JSON válido, sin texto antes ni después, sin bloques de código, con '.
            'un objeto por cada uno de los 7 días, exactamente con esta forma:'."\n".
            '{"monday": {"closed": false, "open": "08:00", "close": "18:00"}, "tuesday": {"closed": false, '.
            '"open": "08:00", "close": "18:00"}, "wednesday": {...}, "thursday": {...}, "friday": {...}, '.
            '"saturday": {...}, "sunday": {"closed": true, "open": null, "close": null}}';
    }

    /**
     * @return array<string, array{closed: bool, open: ?string, close: ?string}>|null
     */
    private function parse(?string $raw): ?array
    {
        if ($raw === null) {
            return null;
        }

        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?|```$/m', '', $raw) ?? $raw;
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return null;
        }

        $schedule = [];

        foreach (array_keys(Business::DAY_LABELS) as $day) {
            $entry = $decoded[$day] ?? null;

            if (! is_array($entry)) {
                return null;
            }

            $open = is_string($entry['open'] ?? null) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $entry['open']) ? $entry['open'] : null;
            $close = is_string($entry['close'] ?? null) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $entry['close']) ? $entry['close'] : null;
            $closed = (bool) ($entry['closed'] ?? false) || ! $open || ! $close;

            $schedule[$day] = [
                'closed' => $closed,
                'open' => $closed ? null : $open,
                'close' => $closed ? null : $close,
            ];
        }

        return $schedule;
    }
}
