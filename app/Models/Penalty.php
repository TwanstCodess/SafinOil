<?php
// app/Models/Penalty.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penalty extends Model
{
    protected $fillable = [
        'employee_id', 'amount', 'penalty_date', 'reason', 'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'penalty_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
