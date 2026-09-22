<?php

namespace App\Console\Commands;

use App\Domain\Social\Models\LiveDestination;
use App\Domain\Social\Models\LiveStream;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Throwable;

#[Signature('live:relay {path : Ruta publicada en MediaMTX}')]
#[Description('Retransmite una señal de Merkamigo hacia sus destinos RTMP habilitados.')]
class RelayLiveDestinations extends Command
{
    private bool $running = true;

    /** @var array<int, array{process: Process, output: string, started_at: float, output_url: string}> */
    private array $relays = [];

    /** @var array<int, float> */
    private array $retryAt = [];

    public function handle(): int
    {
        $path = ltrim((string) $this->argument('path'), '/');
        $stream = LiveStream::query()->where('stream_path', $path)->first();

        if (! $stream) {
            $this->error("No existe un Live para la ruta {$path}.");

            return self::FAILURE;
        }

        if (! is_executable(config('services.live_streaming.ffmpeg_binary'))) {
            $stream->streamingDestinations()->where('is_enabled', true)->update([
                'status' => 'error',
                'last_error' => __('FFmpeg no está instalado o no es ejecutable.'),
            ]);

            return self::FAILURE;
        }

        $this->listenForTermination();
        $inputUrl = rtrim(config('services.live_streaming.rtsp_internal_url'), '/').'/'.$path;

        try {
            while ($this->running) {
                $this->synchronize($stream, $inputUrl);
                usleep(500_000);
            }
        } finally {
            $this->stopAll();
        }

        return self::SUCCESS;
    }

    private function synchronize(LiveStream $stream, string $inputUrl): void
    {
        $destinations = $stream->streamingDestinations()->where('is_enabled', true)->get()->keyBy('id');

        foreach (array_diff(array_keys($this->relays), $destinations->keys()->all()) as $destinationId) {
            $this->stopRelay($destinationId);
        }

        foreach ($destinations as $destination) {
            $relay = $this->relays[$destination->id] ?? null;

            if ($relay && $relay['process']->isRunning()) {
                if ($destination->status !== 'transmitting' && microtime(true) - $relay['started_at'] >= 3) {
                    $destination->update([
                        'status' => 'transmitting',
                        'last_error' => null,
                        'last_connected_at' => now(),
                    ]);
                }

                continue;
            }

            if ($relay) {
                $this->recordFailure($destination, $relay);
                unset($this->relays[$destination->id]);
                $this->retryAt[$destination->id] = microtime(true) + 5;
            }

            if (($this->retryAt[$destination->id] ?? 0) > microtime(true)) {
                continue;
            }

            $this->startRelay($destination, $inputUrl);
        }
    }

    private function startRelay(LiveDestination $destination, string $inputUrl): void
    {
        $outputUrl = rtrim($destination->rtmp_url, '/').'/'.ltrim($destination->stream_key, '/');
        $process = new Process($this->command($inputUrl, $outputUrl));
        $process->setTimeout(null);
        $process->setIdleTimeout(null);

        $destination->update(['status' => 'connecting', 'last_error' => null]);
        $this->relays[$destination->id] = [
            'process' => $process,
            'output' => '',
            'started_at' => microtime(true),
            'output_url' => $outputUrl,
        ];

        try {
            $process->start(function (string $_type, string $output) use ($destination): void {
                $this->relays[$destination->id]['output'] = mb_substr(
                    ($this->relays[$destination->id]['output'] ?? '').$output,
                    -4000,
                );
            });
        } catch (Throwable $exception) {
            $destination->update([
                'status' => 'error',
                'last_error' => str_replace(
                    [$outputUrl, $destination->stream_key],
                    ['[destino protegido]', '[clave protegida]'],
                    $exception->getMessage(),
                ),
            ]);
            unset($this->relays[$destination->id]);
            $this->retryAt[$destination->id] = microtime(true) + 5;
        }
    }

    /** @return array<int, string> */
    private function command(string $inputUrl, string $outputUrl): array
    {
        return [
            config('services.live_streaming.ffmpeg_binary'),
            '-hide_banner', '-loglevel', 'warning', '-nostdin',
            '-rtsp_transport', 'tcp', '-timeout', '15000000',
            '-i', $inputUrl,
            '-map', '0:v:0', '-map', '0:a:0?',
            '-c:v', 'libx264', '-preset', 'veryfast', '-tune', 'zerolatency',
            '-pix_fmt', 'yuv420p', '-profile:v', 'high', '-level:v', '4.1',
            '-r', '30', '-g', '60', '-keyint_min', '60', '-sc_threshold', '0',
            '-b:v', '4500k', '-maxrate', '4500k', '-bufsize', '9000k',
            '-c:a', 'aac', '-b:a', '128k', '-ar', '44100', '-ac', '2',
            '-max_muxing_queue_size', '1024',
            '-f', 'flv', '-flvflags', 'no_duration_filesize',
            $outputUrl,
        ];
    }

    /** @param array{process: Process, output: string, started_at: float, output_url: string} $relay */
    private function recordFailure(LiveDestination $destination, array $relay): void
    {
        $processOutput = $relay['output'].'\n'.$relay['process']->getErrorOutput();
        $error = trim(str_replace(
            [$relay['output_url'], $destination->stream_key],
            ['[destino protegido]', '[clave protegida]'],
            $processOutput,
        ));

        $destination->update([
            'status' => 'error',
            'last_error' => mb_substr($error ?: __('La conexión RTMP terminó inesperadamente.'), -2000),
        ]);
    }

    private function stopRelay(int $destinationId): void
    {
        $relay = $this->relays[$destinationId] ?? null;

        if ($relay && $relay['process']->isRunning()) {
            $relay['process']->stop(3, SIGTERM);
        }

        LiveDestination::query()->whereKey($destinationId)->update(['status' => 'disconnected']);
        unset($this->relays[$destinationId], $this->retryAt[$destinationId]);
    }

    private function stopAll(): void
    {
        foreach (array_keys($this->relays) as $destinationId) {
            $this->stopRelay($destinationId);
        }
    }

    private function listenForTermination(): void
    {
        if (! function_exists('pcntl_async_signals')) {
            return;
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGINT, fn () => $this->running = false);
        pcntl_signal(SIGTERM, fn () => $this->running = false);
    }
}
