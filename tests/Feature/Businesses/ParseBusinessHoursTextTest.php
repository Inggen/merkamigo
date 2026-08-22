<?php

namespace Tests\Feature\Businesses;

use App\Domain\Businesses\Actions\ParseBusinessHoursText;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Autocompletar "Horario por día" a partir del texto libre de horarios
 * (pedido del usuario: no debería tener que llenar cada día a mano si ya
 * escribió "Lun-Sáb 8:00am - 6:00pm").
 */
class ParseBusinessHoursTextTest extends TestCase
{
    use RefreshDatabase;

    private function fakeAi(?string $response): void
    {
        $this->app->bind(GeneratesAssistedText::class, fn () => new class($response) implements GeneratesAssistedText
        {
            public function __construct(private readonly ?string $response) {}

            public function generate(string $prompt, array $context = []): ?string
            {
                return $this->response;
            }
        });
    }

    public function test_parses_a_weekday_range_into_the_structured_schedule(): void
    {
        $this->fakeAi(json_encode([
            'monday' => ['closed' => false, 'open' => '08:00', 'close' => '18:00'],
            'tuesday' => ['closed' => false, 'open' => '08:00', 'close' => '18:00'],
            'wednesday' => ['closed' => false, 'open' => '08:00', 'close' => '18:00'],
            'thursday' => ['closed' => false, 'open' => '08:00', 'close' => '18:00'],
            'friday' => ['closed' => false, 'open' => '08:00', 'close' => '18:00'],
            'saturday' => ['closed' => false, 'open' => '08:00', 'close' => '18:00'],
            'sunday' => ['closed' => true, 'open' => null, 'close' => null],
        ]));

        $schedule = app(ParseBusinessHoursText::class)->handle('Lun-Sáb 8:00am - 6:00pm');

        $this->assertSame(['closed' => false, 'open' => '08:00', 'close' => '18:00'], $schedule['monday']);
        $this->assertSame(['closed' => true, 'open' => null, 'close' => null], $schedule['sunday']);
    }

    public function test_returns_null_for_blank_text_without_calling_the_ai(): void
    {
        $this->fakeAi(null);

        $this->assertNull(app(ParseBusinessHoursText::class)->handle('   '));
    }

    public function test_returns_null_when_the_ai_does_not_answer(): void
    {
        $this->fakeAi(null);

        $this->assertNull(app(ParseBusinessHoursText::class)->handle('Lun-Sáb 8am-6pm'));
    }

    public function test_returns_null_when_the_ai_response_is_not_valid_json(): void
    {
        $this->fakeAi('esto no es json');

        $this->assertNull(app(ParseBusinessHoursText::class)->handle('Lun-Sáb 8am-6pm'));
    }

    public function test_treats_a_day_with_invalid_hours_as_closed(): void
    {
        $this->fakeAi(json_encode([
            'monday' => ['closed' => false, 'open' => 'no es una hora', 'close' => '18:00'],
            'tuesday' => ['closed' => true, 'open' => null, 'close' => null],
            'wednesday' => ['closed' => true, 'open' => null, 'close' => null],
            'thursday' => ['closed' => true, 'open' => null, 'close' => null],
            'friday' => ['closed' => true, 'open' => null, 'close' => null],
            'saturday' => ['closed' => true, 'open' => null, 'close' => null],
            'sunday' => ['closed' => true, 'open' => null, 'close' => null],
        ]));

        $schedule = app(ParseBusinessHoursText::class)->handle('Lunes horario raro');

        $this->assertSame(['closed' => true, 'open' => null, 'close' => null], $schedule['monday']);
    }
}
