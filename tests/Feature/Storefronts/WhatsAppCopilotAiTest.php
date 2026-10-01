<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Billing\Actions\SubscribeToPlan;
use App\Domain\Billing\Models\Plan;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppCopilotAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_whatsapp_copilot_can_use_the_ai_contract_when_available(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio IA'])->business;
        // El Copiloto requiere un plan de pago desde el 2026-09-30.
        $plan = Plan::create([
            'slug' => 'emprendedor', 'name' => 'Emprendedor', 'price_cents' => 4990000,
            'billing_period' => Plan::MENSUAL, 'is_active' => true, 'position' => 1,
        ]);
        app(SubscribeToPlan::class)->handle($business, $plan, $owner);

        $this->app->bind(GeneratesAssistedText::class, fn () => new class implements GeneratesAssistedText
        {
            public function generate(string $prompt, array $context = []): ?string
            {
                return 'Texto mejorado con IA';
            }
        });

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.copiloto', ['business' => $business->id])
            ->set('type', 'presentacion')
            ->set('tone', 'cercano')
            ->call('generate')
            ->assertSet('hasGenerated', true)
            ->assertSet('generated', 'Texto mejorado con IA');
    }
}
