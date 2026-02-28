<?php
// app/Models/Customer.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Sale;
use App\Models\CreditPayment;
class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'name', 'phone', 'address', 'identity_number',
        'vehicle_number', 'total_credit', 'total_paid',
        'current_debt', 'notes', 'is_active'
    ];

    protected $casts = [
        'total_credit' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'current_debt' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function creditPayments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }

    /**
     * نوێکردنەوەی قەرزی کڕیار
     */
    public function updateDebt()
    {
        $this->total_credit = $this->sales()
            ->where('payment_type', 'credit')
            ->sum('total_price');

        $this->total_paid = $this->creditPayments()->sum('amount');
        $this->current_debt = $this->total_credit - $this->total_paid;
        $this->save();

        return $this;
    }

    /**
     * فۆرمەتی قەرز
     */
    public function getFormattedDebtAttribute(): string
    {
        return number_format($this->current_debt) . ' دینار';
    }

    /**
     * ڕەنگی قەرز بۆ badge
     */
    public function getDebtColorAttribute(): string
    {
        if ($this->current_debt == 0) return 'success';
        if ($this->current_debt < 100000) return 'warning';
        return 'danger';
    }
}
