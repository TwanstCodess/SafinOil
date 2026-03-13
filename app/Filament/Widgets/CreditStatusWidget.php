<?php
// app/Filament/Widgets/CreditStatusWidget.php
namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CreditStatusWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $totalCustomers    = Customer::count();
        $customersWithDebt = Customer::where('current_debt', '>', 0)->count();

        $totalCredit    = Sale::where('payment_type', 'credit')->sum('total_price');
        $totalPaid      = Sale::where('payment_type', 'credit')->sum('paid_amount');
        $remainingCredit = $totalCredit - $totalPaid;

        $overdueCredits = Sale::where('payment_type', 'credit')
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->count();

        // ✅ قازانجی چاوەڕوانکراو لە قەرزەکان (بڕی ماوە)
        $expectedProfitFromCredit = Sale::join('categories', 'sales.category_id', '=', 'categories.id')
            ->where('sales.payment_type', 'credit')
            ->where('sales.status', '!=', 'paid')
            ->selectRaw('SUM(sales.liters * (sales.price_per_liter - categories.purchase_price)) as profit')
            ->value('profit') ?? 0;

        return [
            Stat::make('کڕیارانی قەرزدار', $customersWithDebt . ' لە ' . $totalCustomers)
                ->description('ژمارەی کڕیارانی قەرزدار')
                ->descriptionIcon('heroicon-m-users')
                ->color($customersWithDebt > 0 ? 'warning' : 'success'),

            Stat::make('کۆی قەرزەکان', $this->fmt($totalCredit))
                ->description('کۆی گشتی قەرز (دراوە + ماوە)')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('warning'),

            Stat::make('قەرزی ماوە', $this->fmt($remainingCredit))
                ->description('پارەی بەسەر قەرزداران — قازانج: ' . $this->fmt($expectedProfitFromCredit))
                ->descriptionIcon('heroicon-m-clock')
                ->color($remainingCredit > 0 ? 'danger' : 'success'),

            Stat::make('قەرزی بەسەرچوو', $overdueCredits . ' فرۆشتن')
                ->description('ژمارەی قەرزە بەسەرچووەکان')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueCredits > 0 ? 'danger' : 'gray'),
        ];
    }

    private function fmt(float $amount): string
    {
        $amount = abs($amount);
        if ($amount >= 1_000_000) return number_format($amount / 1_000_000, 2) . ' ملیۆن';
        if ($amount >= 1_000)     return number_format($amount / 1_000, 1) . ' هەزار';
        return number_format($amount);
    }
}
