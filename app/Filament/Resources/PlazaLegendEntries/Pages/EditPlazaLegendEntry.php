<?php

namespace App\Filament\Resources\PlazaLegendEntries\Pages;

use App\Filament\Resources\PlazaLegendEntries\PlazaLegendEntryResource;
use Filament\Resources\Pages\EditRecord;

class EditPlazaLegendEntry extends EditRecord
{
    protected static string $resource = PlazaLegendEntryResource::class;

    /**
     * Mapear un objeto del catálogo a este color es lo que lo marca como
     * "confirmado" — no hay un toggle aparte que el admin pueda olvidar.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['status'] = filled($data['object_template_id'] ?? null) ? 'confirmado' : 'pendiente';

        return $data;
    }
}
