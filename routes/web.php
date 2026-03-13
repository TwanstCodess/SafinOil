<?php

use App\Http\Controllers\ProfileController;
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

// ڕووتی چوونەژوورەوە
Route::get('/login', LoginForm::class)
    ->middleware('guest')
    ->name('login');

// ڕووتەکانی پڕۆفایل (ئەم بەشە زیاد بکە)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ڕووتەکانی پێویست بۆ Breeze (پاسۆردی لەبیرکراو و تۆمارکردن)
require __DIR__.'/auth.php';

// ڕووتی داشبۆرد
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});
