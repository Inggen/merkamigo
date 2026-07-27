<?php

use App\Http\Controllers\ClientesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmprendedoresController;
use App\Http\Controllers\ExperienceController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::view('terminos', 'legal.terminos')->name('terminos');
Route::view('privacidad', 'legal.privacidad')->name('privacidad');

Route::get('emprendedores/bienvenida', [EmprendedoresController::class, 'bienvenida'])
    ->name('emprendedores.bienvenida');

Route::post('experience', [ExperienceController::class, 'update'])
    ->middleware('throttle:30,1')
    ->name('experience.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('clientes', [ClientesController::class, 'home'])->name('clientes.home');

    Route::get('emprendedores', [EmprendedoresController::class, 'home'])->name('emprendedores.home');

    Route::livewire('emprendedores/crear-vitrina', 'pages::emprendedores.crear-vitrina')
        ->name('emprendedores.crear-vitrina');
});

require __DIR__.'/settings.php';
