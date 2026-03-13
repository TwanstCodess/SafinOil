<?php

use App\Http\Controllers\DashboardController;
use App\Livewire\Auth\LoginForm;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// ڕووتی چوونەژوورەوە (ئاماژە بە کۆمپۆنێنتی Livewire)
Route::get('/login', LoginForm::class)
    ->middleware('guest')
    ->name('login');

// ڕووتەکانی پێویست بۆ Breeze (وەک تۆمارکردن و پاسۆردی لەبیرکراو)
// ئەمانە خۆکارانە لە فایلی routes/auth.php ڕێکخراون
require __DIR__.'/auth.php';

// ڕووتی داشبۆرد (پاراستن بە middlewareی auth)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});
