<?php

use Illuminate\Support\Facades\Route;

/*
 * The farm itself is intentionally public: the exercise brief states that
 * authentication is not required. Fortify's auth and settings routes remain
 * available but are not needed to use the application.
 */
Route::redirect('/', '/dashboard')->name('home');

Route::inertia('dashboard', 'Dashboard')->name('dashboard');

require __DIR__.'/settings.php';
