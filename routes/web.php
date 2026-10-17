<?php

declare(strict_types=1);

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
    return view('welcome');
})->name('home');
