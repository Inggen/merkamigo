<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Domain\Storefronts\Notifications\StorefrontPublished;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StorefrontPublishedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_receives_the_branded_email_only_on_the_first_publication(): void
    {
        Notification::fake();

        $owner = User::factory()->create([
            'name' => 'Ana Emprendedora',
            'email' => 'ana@example.com',
        ]);
        $business = $this->completeBusiness($owner);

        app(PublishStorefront::class)->handle($business, $owner);
        app(PublishStorefront::class)->handle($business, $owner);

        Notification::assertSentToTimes($owner, StorefrontPublished::class, 1);
        Notification::assertSentTo($owner, StorefrontPublished::class, function (StorefrontPublished $notification, array $channels) use ($business, $owner): bool {
            $mail = $notification->toMail($owner);

            return $channels === ['mail']
                && $mail->subject === "¡Tu vitrina {$business->name} ya está publicada!"
                && $mail->view === [
                    'html' => 'mail.storefronts.published',
                    'text' => 'mail.storefronts.published-text',
                ]
                && $mail->viewData['storefrontUrl'] === route('vitrinas.show', $business)
                && $mail->viewData['plansUrl'] === route('emprendedores.negocios.plan', $business);
        });
    }

    private function completeBusiness(User $owner): Business
    {
        $municipality = Municipality::create([
            'name' => 'Cajicá',
            'slug' => 'cajica',
            'department' => 'Cundinamarca',
            'is_active' => true,
        ]);
        $category = Category::create([
            'name' => 'Alimentos',
            'slug' => 'alimentos',
            'is_active' => true,
        ]);

        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería Ana',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos todos los días.',
        ])->business;

        $business->update(['logo_path' => 'businesses/panaderia/logo.jpg']);
        app(CreateProduct::class)->handle($business, [
            'name' => 'Pan artesanal',
            'type' => 'producto',
            'price_type' => 'exacto',
            'price' => 8000,
        ], [], $owner);

        return $business;
    }
}
