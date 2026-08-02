<?php

namespace App\Support\Ai;

use App\Support\Ai\Contracts\TranscribesAudio;

/**
 * Implementación nula mientras no se conecte un proveedor real de
 * transcripción. Mantiene estable el contrato para el wizard y futuros
 * copilotos sin forzar una integración prematura.
 */
class NullAudioTranscriber implements TranscribesAudio
{
    public function transcribe(string $audioPath): ?string
    {
        return null;
    }
}
