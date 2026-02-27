<?php
// app/Models/Type.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Type extends Model
{
    protected $fillable = [
        'name', 'key', 'color', 'description'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * وەرگێڕانی ڕەنگ بۆ Filament badge
     */
    public function getFilamentColor(): string
    {
        return match($this->key) {
            'fuel' => 'warning',
            'oil' => 'success',
            'gas' => 'info',
            default => 'gray',
        };
    }
}
