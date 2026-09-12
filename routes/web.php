<?php

use App\Http\Controllers\AnimalController;
use App\Http\Controllers\PaddockController;
use Illuminate\Support\Facades\Route;

/*
 * The farm itself is intentionally public: the exercise brief states that
 * authentication is not required. Fortify's auth and settings routes remain
 * available but are not needed to use the application.
 */
Route::redirect('/', '/dashboard')->name('home');

Route::inertia('dashboard', 'Dashboard')->name('dashboard');

Route::resource('animals', AnimalController::class)->except(['destroy']);
Route::resource('paddocks', PaddockController::class)->except(['destroy']);

require __DIR__.'/settings.php';
