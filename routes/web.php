<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Public Website & Portal)
|--------------------------------------------------------------------------
|
| Here is where you can register public website and marketing routes
| for your application.
|
*/

Route::get('/', function () {
    if (Auth::guard('landlord')->check()) {
        return redirect()->route('landlord.dashboard');
    }

    if (Auth::guard('web')->check()) {
        return redirect()->route('portal.dashboard');
    }

    return redirect()->route('portal.dashboard');
})->name('home');

Route::get('/login', function () {
    if (Auth::guard('landlord')->check()) {
        return redirect()->route('landlord.dashboard');
    }

    if (Auth::guard('web')->check()) {
        return redirect()->route('portal.dashboard');
    }

    return redirect()->route('portal.login');
});
