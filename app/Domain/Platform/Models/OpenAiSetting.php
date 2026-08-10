<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuración singleton de OpenAI editable desde el admin. Mantiene la
 * integración desacoplada del `.env`: si no se configura aquí, cae a
 * `config('services.openai.*')`. Si está apagado, cualquier consumidor de
 * IA debe fallar en silencio y volver a su flujo sin IA.
 */
class OpenAiSetting extends Model
{
    protected $table = 'openai_settings';

    protected $fillable = [
        'enabled',
        'entrepreneur_copilot_enabled',
        'model',
        'api_key',
        'base_url',
        'timeout_seconds',
        'max_output_tokens',
        'temperature',
        'system_prompt',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'entrepreneur_copilot_enabled' => 'boolean',
        'timeout_seconds' => 'integer',
        'max_output_tokens' => 'integer',
        'temperature' => 'float',
    ];

    protected $hidden = [
        'api_key',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'enabled' => (bool) config('services.openai.enabled', false),
            'entrepreneur_copilot_enabled' => (bool) config('services.openai.entrepreneur_copilot_enabled', false),
            'model' => config('services.openai.model'),
            'base_url' => config('services.openai.base_url', 'https://api.openai.com/v1'),
            'timeout_seconds' => (int) config('services.openai.timeout', 30),
            'max_output_tokens' => filled(config('services.openai.max_output_tokens'))
                ? (int) config('services.openai.max_output_tokens')
                : null,
            'temperature' => filled(config('services.openai.temperature'))
                ? (float) config('services.openai.temperature')
                : null,
            'system_prompt' => config('services.openai.system_prompt'),
        ]);
    }

    public function isEnabled(): bool
    {
        return $this->enabled
            && filled($this->apiKey())
            && filled($this->model());
    }

    public function entrepreneurCopilotEnabled(): bool
    {
        return $this->isEnabled() && $this->entrepreneur_copilot_enabled;
    }

    public function apiKey(): ?string
    {
        $apiKey = $this->api_key ?: config('services.openai.api_key');

        return is_string($apiKey) ? trim($apiKey) : $apiKey;
    }

    public function model(): ?string
    {
        return $this->model ?: config('services.openai.model');
    }

    public function baseUrl(): string
    {
        return rtrim((string) ($this->base_url ?: config('services.openai.base_url', 'https://api.openai.com/v1')), '/');
    }

    public function timeoutSeconds(): int
    {
        return max(1, (int) ($this->timeout_seconds ?: config('services.openai.timeout', 30)));
    }

    public function maxOutputTokens(): ?int
    {
        return $this->max_output_tokens ?: (filled(config('services.openai.max_output_tokens'))
            ? (int) config('services.openai.max_output_tokens')
            : null);
    }

    public function temperature(): ?float
    {
        return $this->temperature ?? (filled(config('services.openai.temperature'))
            ? (float) config('services.openai.temperature')
            : null);
    }

    public function systemPrompt(): ?string
    {
        return $this->system_prompt ?: config('services.openai.system_prompt');
    }
}
