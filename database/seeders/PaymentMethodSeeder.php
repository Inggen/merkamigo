<?php

namespace Database\Seeders;

use App\Domain\Businesses\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Formas de pago iniciales (pedido del usuario) para que el negocio ya
 * tenga opciones de dónde escoger sin depender de que un admin las cree
 * primero. Los logos son insignias genéricas con las iniciales/nombre de
 * cada marca en su color de referencia — no reproducen el logotipo real
 * de Nequi/Visa/etc. (propiedad de cada marca); el admin puede
 * reemplazarlas por el logo oficial desde Filament en cualquier momento.
 */
class PaymentMethodSeeder extends Seeder
{
    /**
     * @return array<int, array{name: string, background: string, text: string, label: string}>
     */
    private function defaults(): array
    {
        return [
            ['name' => 'Efectivo', 'background' => '#16a34a', 'text' => '#ffffff', 'label' => '$'],
            ['name' => 'Nequi', 'background' => '#eb0868', 'text' => '#ffffff', 'label' => 'N'],
            ['name' => 'Daviplata', 'background' => '#e4002b', 'text' => '#ffffff', 'label' => 'D'],
            ['name' => 'Transferencia bancaria', 'background' => '#1d4ed8', 'text' => '#ffffff', 'label' => 'TB'],
            ['name' => 'PSE', 'background' => '#004990', 'text' => '#ffffff', 'label' => 'PSE'],
            ['name' => 'Visa', 'background' => '#1a1f71', 'text' => '#ffffff', 'label' => 'VISA'],
            ['name' => 'Mastercard', 'background' => '#242424', 'text' => '#ffffff', 'label' => 'MC'],
            ['name' => 'Pago contraentrega', 'background' => '#b45309', 'text' => '#ffffff', 'label' => '📦'],
        ];
    }

    public function run(): void
    {
        foreach ($this->defaults() as $position => $method) {
            $slug = Str::slug($method['name']);
            $logoPath = "payment-methods/{$slug}.svg";

            if (! Storage::disk('public')->exists($logoPath)) {
                Storage::disk('public')->put($logoPath, $this->badgeSvg($method['label'], $method['background'], $method['text']));
            }

            PaymentMethod::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $method['name'],
                    'logo_path' => $logoPath,
                    'is_active' => true,
                    'position' => $position,
                ],
            );
        }
    }

    private function badgeSvg(string $label, string $background, string $textColor): string
    {
        $label = e($label);

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120">
            <rect width="120" height="120" rx="24" fill="{$background}"/>
            <text x="60" y="68" font-family="Arial, sans-serif" font-size="34" font-weight="bold" fill="{$textColor}" text-anchor="middle">{$label}</text>
        </svg>
        SVG;
    }
}
