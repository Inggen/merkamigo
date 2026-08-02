<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Trust\Models\BusinessVerification;
use App\Domain\Trust\Notifications\VerificationExpiringSoon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * 3.1 del TODO: recordatorios de renovación de verificación.
 */
class VerificationExpiryReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_command_notifies_members_of_a_business_whose_verification_expires_soon(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Por Vencer'])->business;

        $verification = BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::VERIFICADA,
            'level' => 'basica',
            'expires_at' => now()->addDays(5),
        ]);

        $this->artisan('trust:remind-verification-expiry')->assertSuccessful();

        Notification::assertSentTo($owner, VerificationExpiringSoon::class);
        $this->assertNotNull($verification->fresh()->expiry_reminder_sent_at);
    }

    public function test_the_reminder_is_not_sent_twice(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Ya Avisado'])->business;

        BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::VERIFICADA,
            'level' => 'basica',
            'expires_at' => now()->addDays(5),
            'expiry_reminder_sent_at' => now()->subDay(),
        ]);

        $this->artisan('trust:remind-verification-expiry')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_a_verification_far_from_expiring_is_not_notified(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Vigente'])->business;

        BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::VERIFICADA,
            'level' => 'basica',
            'expires_at' => now()->addDays(60),
        ]);

        $this->artisan('trust:remind-verification-expiry')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
