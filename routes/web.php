<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('emprendedores/crear-vitrina', 'pages::emprendedores.crear-vitrina')
        ->name('emprendedores.crear-vitrina');
});

require __DIR__.'/settings.php';
