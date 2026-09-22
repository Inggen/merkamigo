<?php

namespace App\Domain\Social\Actions;

use App\Domain\Social\Models\LiveStream;
use App\Domain\Social\Models\LiveStreamPoll;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageLivePoll
{
    /** @param array<int, string> $options */
    public function save(LiveStream $stream, string $question, array $options, User $user): LiveStreamPoll
    {
        abort_if($stream->status === LiveStream::FINALIZADO, 422, __('La transmisión ya terminó.'));

        $validated = validator(compact('question', 'options'), [
            'question' => ['required', 'string', 'max:120'],
            'options' => ['required', 'array', 'min:2', 'max:4'],
            'options.*' => ['required', 'string', 'max:80', 'distinct:ignore_case'],
        ])->validate();

        return DB::transaction(function () use ($stream, $validated, $user): LiveStreamPoll {
            LiveStream::query()->lockForUpdate()->findOrFail($stream->id);

            if ($stream->polls()->count() >= 5) {
                throw ValidationException::withMessages([
                    'question' => __('Puedes guardar máximo 5 encuestas por transmisión.'),
                ]);
            }

            return $stream->polls()->create([
                'user_id' => $user->id,
                'question' => $validated['question'],
                'options' => array_values($validated['options']),
                'closed_at' => now(),
            ]);
        });
    }

    /** @param array<int, string> $options */
    public function publish(LiveStream $stream, string $question, array $options, User $user): LiveStreamPoll
    {
        $poll = $this->save($stream, $question, $options, $user);
        $this->show($poll);

        return $poll->refresh();
    }

    public function show(LiveStreamPoll $poll): void
    {
        abort_unless($poll->liveStream->isLive(), 422, __('Inicia la transmisión antes de mostrar una encuesta.'));

        DB::transaction(function () use ($poll): void {
            $poll->liveStream->polls()->whereNull('closed_at')->whereKeyNot($poll->id)->update(['closed_at' => now()]);
            $poll->update(['closed_at' => null]);
        });
    }

    public function hide(LiveStreamPoll $poll): void
    {
        if ($poll->isOpen()) {
            $poll->update(['closed_at' => now()]);
        }
    }

    public function close(LiveStreamPoll $poll): void
    {
        $this->hide($poll);
    }

    public function vote(LiveStreamPoll $poll, int $optionIndex, Request $request, ?User $user): void
    {
        abort_unless($poll->isOpen() && $poll->liveStream->isLive(), 422, __('La encuesta ya terminó.'));
        abort_unless(array_key_exists($optionIndex, $poll->options), 422, __('Selecciona una opción válida.'));

        $identity = $user ? "user:{$user->id}" : 'session:'.$request->session()->getId();
        $visitorHash = hash_hmac('sha256', $identity, (string) config('app.key'));

        $poll->votes()->updateOrCreate(
            ['visitor_hash' => $visitorHash],
            ['user_id' => $user?->id, 'option_index' => $optionIndex],
        );
    }
}
