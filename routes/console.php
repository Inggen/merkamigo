<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 2.1/2.3 del TODO: cerrar por vencimiento las necesidades publicadas.
Schedule::command('needs:expire-overdue')->daily();
