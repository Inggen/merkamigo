<?php

namespace App\Support\Ai\Contracts;

/**
 * Contrato interno para transcripción de audio a texto (5.4 del TODO),
 * pensado para "Mi Merkamigo en cinco minutos" (1.2) cuando se elija un
 * proveedor de IA. Sin implementación todavía — elegir proveedor es una
 * decisión de producto/costo, no de código (ver
 * docs/architecture/decisiones.md). Esta interfaz solo deja el punto de
 * extensión listo para no acoplar el wizard a un SDK concreto el día que
 * se decida.
 */
interface TranscribesAudio
{
    /**
     * @param  string  $audioPath  Ruta al archivo de audio en el disco configurado.
     * @return string|null null si no se pudo transcribir.
     */
    public function transcribe(string $audioPath): ?string;
}
