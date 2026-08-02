<?php

namespace App\Support\Ai\Contracts;

/**
 * Contrato interno para texto asistido por IA (5.4 del TODO), pensado
 * para "Mi Merkamigo en cinco minutos" (1.2) y el Copiloto de WhatsApp
 * (1.7/4.4) cuando se elija un proveedor. Sin implementación todavía —
 * mismo motivo que `TranscribesAudio`. El Copiloto de WhatsApp actual
 * (`GenerateWhatsAppPromotion`) sigue generando texto a partir de datos
 * reales sin IA, y seguirá funcionando igual si nunca se implementa esta
 * interfaz.
 */
interface GeneratesAssistedText
{
    /**
     * @param  array<string, mixed>  $context  Datos reales del negocio/producto — nunca se debe inventar información fuera de este contexto.
     * @return string|null null si no se pudo generar.
     */
    public function generate(string $prompt, array $context = []): ?string;
}
