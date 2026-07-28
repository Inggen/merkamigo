<?php

use App\Http\Controllers\ClientesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmprendedoresController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\PlazaController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\VitrinaController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::view('terminos', 'legal.terminos')->name('terminos');
Route::view('privacidad', 'legal.privacidad')->name('privacidad');
Route::view('reglas-comunidad', 'legal.reglas-comunidad')->name('reglas-comunidad');
Route::view('como-funciona', 'public.como-funciona')->name('como-funciona');
Route::view('soporte', 'public.soporte')->name('soporte');
Route::view('preguntas-frecuentes', 'public.preguntas-frecuentes', ['faqs' => config('faq.preguntas')])
    ->name('preguntas-frecuentes');
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('emprendedores/bienvenida', [EmprendedoresController::class, 'bienvenida'])
    ->name('emprendedores.bienvenida');

Route::post('experience', [ExperienceController::class, 'update'])
    ->middleware('throttle:30,1')
    ->name('experience.update');

// Plaza y vitrina pública (1.3, 1.5 del TODO) — sin registro obligatorio.
Route::get('municipios', [PlazaController::class, 'municipios'])->name('municipios');
Route::get('categorias', [PlazaController::class, 'categorias'])->name('categorias');
Route::get('buscar', [PlazaController::class, 'buscar'])->name('buscar');
Route::get('plaza/{municipio:slug}', [PlazaController::class, 'show'])->name('plaza.show');
Route::get('plaza/{municipio:slug}/categorias/{categoria:slug}', [PlazaController::class, 'category'])->name('plaza.category');

Route::get('m/{business:slug}', [VitrinaController::class, 'show'])->name('vitrinas.show');
Route::get('m/{business:slug}/productos/{product:slug}', [VitrinaController::class, 'product'])->name('vitrinas.product');
Route::get('m/{business:slug}/qr', [VitrinaController::class, 'qr'])->name('vitrinas.qr');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('clientes', [ClientesController::class, 'home'])->name('clientes.home');
    Route::get('clientes/favoritos', [ClientesController::class, 'favoritos'])->name('clientes.favoritos');
    Route::post('clientes/municipio', [ClientesController::class, 'setMunicipio'])->name('clientes.municipio');

    Route::get('emprendedores', [EmprendedoresController::class, 'home'])->name('emprendedores.home');

    Route::livewire('emprendedores/crear-vitrina', 'pages::emprendedores.crear-vitrina')
        ->name('emprendedores.crear-vitrina');

    Route::middleware('business.team')->prefix('emprendedores/negocios/{business}')->name('emprendedores.negocios.')->group(function () {
        Route::livewire('vitrina', 'pages::emprendedores.negocios.vitrina')->name('vitrina');
        Route::livewire('productos', 'pages::emprendedores.negocios.productos')->name('productos');
        Route::livewire('colaboradores', 'pages::emprendedores.negocios.colaboradores')->name('colaboradores');
        Route::get('vista-previa', [EmprendedoresController::class, 'vistaPrevia'])->name('vista-previa');
        Route::get('compartir', [EmprendedoresController::class, 'compartir'])->name('compartir');
    });
});

require __DIR__.'/settings.php';
