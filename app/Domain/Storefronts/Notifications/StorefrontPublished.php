<?php

namespace App\Domain\Storefronts\Notifications;

use App\Domain\Billing\Models\Plan;
use App\Domain\Businesses\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StorefrontPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Business $business) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $data = [
            'name' => $notifiable->name,
            'businessName' => $this->business->name,
            'storefrontUrl' => route('vitrinas.show', $this->business),
            'plansUrl' => route('emprendedores.negocios.plan', $this->business),
            'plans' => $plans,
        ];

        return (new MailMessage)
            ->subject(__('¡Tu vitrina :business ya está publicada!', [
                'business' => $this->business->name,
            ]))
            ->view([
                'html' => 'mail.storefronts.published',
                'text' => 'mail.storefronts.published-text',
            ], $data);
    }
}
