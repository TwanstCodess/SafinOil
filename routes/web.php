<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PayrollController;
use App\Livewire\Auth\LoginForm;
use App\Livewire\Payroll\EmployeeSalaries;
use App\Livewire\Payroll\SalaryPayment;
use App\Livewire\Payroll\SalaryHistory;
use App\Livewire\Payroll\PenaltyManagement;
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

// ڕووتەکانی مووچە
Route::middleware(['auth'])->prefix('payroll')->name('payroll.')->group(function () {
    Route::get('/', [PayrollController::class, 'index'])->name('index');
    Route::get('/employees', [PayrollController::class, 'employeeSalaries'])->name('employees');
    Route::get('/employees/{employee}', [PayrollController::class, 'employeeSalaries'])->name('employee.detail');
    Route::get('/salary-report', [PayrollController::class, 'salaryReport'])->name('salary-report');
    Route::get('/penalty-report', [PayrollController::class, 'penaltyReport'])->name('penalty-report');

    // Livewire Components
    Route::get('/salaries', EmployeeSalaries::class)->name('salaries');
    Route::get('/payment', SalaryPayment::class)->name('payment');
    Route::get('/history', SalaryHistory::class)->name('history');
    Route::get('/penalties', PenaltyManagement::class)->name('penalties');
});
