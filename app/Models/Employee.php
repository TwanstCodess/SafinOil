<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable
{
    protected $table = 'employees';

    protected $fillable = [
        'user_id', 'name', 'position', 'phone', 'salary', 'hire_date', 'is_active', 'email', 'password'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'hire_date' => 'date',
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    // ===================== Relationships =====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(Salary::class);
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(Penalty::class);
    }

    // ===================== Accessors =====================

    public function getTotalSalariesAttribute(): float
    {
        return $this->salaries()->sum('net_amount');
    }

    public function getTotalPenaltiesAttribute(): float
    {
        return $this->penalties()->sum('amount');
    }

    public function getNetIncomeAttribute(): float
    {
        return $this->total_salaries - $this->total_penalties;
    }

    public function getFormattedSalaryAttribute(): string
    {
        return number_format($this->salary) . ' د.ع';
    }

    public function getFormattedTotalSalariesAttribute(): string
    {
        return number_format($this->total_salaries) . ' د.ع';
    }

    public function getFormattedTotalPenaltiesAttribute(): string
    {
        return number_format($this->total_penalties) . ' د.ع';
    }

    public function getFormattedNetIncomeAttribute(): string
    {
        return number_format($this->net_income) . ' د.ع';
    }
}
