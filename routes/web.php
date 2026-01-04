<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('chat', [\App\Http\Controllers\ChatController::class, 'index'])->name('chat');
    Route::post('chat/send', [\App\Http\Controllers\ChatController::class, 'send'])->name('chat.send');
    Route::post('chat/confirm-action', [\App\Http\Controllers\ChatController::class, 'confirmAction'])->name('chat.confirm');
});

require __DIR__.'/settings.php';
