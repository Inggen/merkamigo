<?php

namespace App\Domain\Social\Notifications;

use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Domain\Social\Models\LiveStream;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LiveStarted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly LiveStream $stream) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', PushChannel::class];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'live_started',
            'live_stream_id' => $this->stream->id,
            'business_id' => $this->stream->business_id,
            'message' => __(':business está transmitiendo en vivo.', ['business' => $this->stream->business->name]),
            'url' => route('live.show', $this->stream),
        ];
    }

    /** @return array{title: string, body: string, url: string} */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('En vivo ahora'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
