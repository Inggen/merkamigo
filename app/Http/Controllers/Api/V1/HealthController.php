<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * `GET /api/v1/health` — exigido por 0.3 del TODO: health checks de la
 * aplicación, base de datos, Redis, colas y almacenamiento.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check(function () {
                DB::select('select 1');

                return true;
            }),
            'redis' => $this->check(fn () => Redis::connection()->ping()),
            'queue' => $this->check(fn () => Redis::connection('default')->ping()),
            'storage' => $this->check(function () {
                $path = 'health-check-'.Str::random(8).'.txt';
                Storage::disk(config('filesystems.default'))->put($path, 'ok');
                $exists = Storage::disk(config('filesystems.default'))->exists($path);
                Storage::disk(config('filesystems.default'))->delete($path);

                return $exists;
            }),
        ];

        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function check(callable $probe): bool
    {
        try {
            return (bool) $probe();
        } catch (Throwable) {
            return false;
        }
    }
}
