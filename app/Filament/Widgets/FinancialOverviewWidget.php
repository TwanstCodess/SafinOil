<?php
// app/Filament/Widgets/FinancialOverviewWidget.php
namespace App\Filament\Widgets;

use App\Models\Cash;
use App\Models\Sale;
use App\Models\FuelPurchase;
use App\Models\Expense;
use App\Models\Salary;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class FinancialOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $cash = Cash::first();
        $today = Carbon::today();

        // فرۆشتنی ئەمڕۆ
        $todaySales = Sale::whereDate('sale_date', $today)->sum('total_price');

        // کڕینی ئەمڕۆ
        $todayPurchases = FuelPurchase::whereDate('purchase_date', $today)->sum('total_price');

        // خەرجی ئەمڕۆ
        $todayExpenses = Expense::whereDate('expense_date', $today)->sum('amount');

        // مووچەی ئەمڕۆ
        $todaySalaries = Salary::whereDate('payment_date', $today)->sum('net_amount');

        // کۆی خەرجی ئەمڕۆ
        $todayTotalExpense = $todayPurchases + $todayExpenses + $todaySalaries;

        // قەرزەکانی ئەمڕۆ
        $todayCredits = Sale::whereDate('sale_date', $today)
            ->where('payment_type', 'credit')
            ->count();

        return [
            Stat::make('ڕەوشتی قاسە', $this->formatMoney($cash->balance ?? 0))
                ->description('کۆی گشتی پارە لە قاسەدا')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary')
                ->chart([7, 3, 10, 5, 15, 8, 12])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('سەرمایە', $this->formatMoney($cash->capital ?? 0))
                ->description('سەرمایەی سەرەتایی')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([10, 8, 12, 6, 14, 9, 11]),

            Stat::make('قازانجی خاوێن', $this->formatMoney($cash->profit ?? 0))
                ->description('کۆی داهات - کۆی خەرجی')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($cash->profit >= 0 ? 'success' : 'danger')
                ->chart([5, 10, 8, 12, 7, 15, 9]),

            Stat::make('فرۆشتی ئەمڕۆ', $this->formatMoney($todaySales))
                ->description('کۆی فرۆشتنەکانی ئەمڕۆ')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info')
                ->chart([3, 5, 2, 6, 4, 7, 3]),

            Stat::make('کڕینی ئەمڕۆ', $this->formatMoney($todayPurchases))
                ->description('کۆی کڕینەکانی ئەمڕۆ')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning')
                ->chart([2, 4, 3, 5, 2, 6, 4]),

            Stat::make('کۆی خەرجی ئەمڕۆ', $this->formatMoney($todayTotalExpense))
                ->description('مووچە + کڕین + خەرجی')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart([4, 6, 3, 7, 5, 8, 4]),

            Stat::make('قەرزەکانی ئەمڕۆ', $todayCredits)
                ->description('ژمارەی فرۆشتنی قەرزی ئەمڕۆ')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('gray')
                ->chart([1, 2, 1, 3, 2, 4, 2]),

            Stat::make('کۆگا', $this->formatLiters(\App\Models\Category::sum('stock_liters')))
                ->description('کۆی گشتی بەنزین لە کۆگا')
                ->descriptionIcon('heroicon-o-beaker')
                ->color('warning'),
        ];
    }

    private function formatMoney($amount)
    {
        if ($amount >= 1000000) {
            return number_format($amount / 1000000, 2) . ' ملیۆن';
        } elseif ($amount >= 1000) {
            return number_format($amount / 1000, 2) . ' هەزار';
        }
        return number_format($amount);
    }

    private function formatLiters($liters)
    {
        if ($liters >= 1000) {
            return number_format($liters / 1000, 2) . ' هەزار لیتر';
        }
        return number_format($liters) . ' لیتر';
    }
}
