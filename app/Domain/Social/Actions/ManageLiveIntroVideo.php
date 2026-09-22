<?php

namespace App\Domain\Social\Actions;

use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Social\Models\LiveStream;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ManageLiveIntroVideo
{
    public function store(LiveStream $stream, UploadedFile $video, User $actor): LiveStream
    {
        $path = $video->store("live-intros/{$stream->id}", 'public');

        if ($stream->intro_video_path) {
            Storage::disk('public')->delete($stream->intro_video_path);
        }

        $stream->update([
            'intro_video_path' => $path,
            'intro_video_active' => true,
        ]);

        app(RecordAuditLog::class)->handle($actor, 'live.intro_video_saved', $stream);

        return $stream->refresh();
    }

    public function toggle(LiveStream $stream, User $actor): LiveStream
    {
        abort_unless($stream->intro_video_path, 422, __('Primero selecciona un video de inicio.'));
        abort_if($stream->status === LiveStream::FINALIZADO, 422, __('La transmisión ya finalizó.'));

        $stream->update(['intro_video_active' => ! $stream->intro_video_active]);

        app(RecordAuditLog::class)->handle($actor, 'live.intro_video_toggled', $stream, [
            'active' => $stream->intro_video_active,
        ]);

        return $stream->refresh();
    }
}
