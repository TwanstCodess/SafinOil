<?php
// app/Models/Employee.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'name', 'position', 'phone', 'salary', 'hire_date', 'is_active'
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function salaries(): HasMany
    {
        return $this->hasMany(Salary::class);
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(Penalty::class);
    }
}
