<?php
namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Models\FuelPurchase;
use App\Models\Expense;
use App\Models\Salary;
use App\Models\Cash;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProfitStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // ✅ داهاتی فرۆشتن (ئەم مانگە)
        $thisMonth = Carbon::now();

        $monthlySalesRevenue = Sale::whereMonth('sale_date', $thisMonth->month)
            ->whereYear('sale_date', $thisMonth->year)
            ->sum('total_price');

        // ✅ قازانجی ڕاستەقینە = (نرخی فرۆشتن - نرخی کڕین) × لیتر
        // بۆ هەر فرۆشتنێک: profit = liters × (price_per_liter - category.purchase_price)
        $monthlyProfit = Sale::join('categories', 'sales.category_id', '=', 'categories.id')
            ->whereMonth('sales.sale_date', $thisMonth->month)
            ->whereYear('sales.sale_date', $thisMonth->year)
            ->selectRaw('SUM(sales.liters * (sales.price_per_liter - categories.purchase_price)) as profit')
            ->value('profit') ?? 0;

        // ✅ کۆی قازانجی گشتی (هەموو کات)
        $totalProfit = Sale::join('categories', 'sales.category_id', '=', 'categories.id')
            ->selectRaw('SUM(sales.liters * (sales.price_per_liter - categories.purchase_price)) as profit')
            ->value('profit') ?? 0;

        // ✅ خەرجیەکان (ئەم مانگە): کڕین + خەرجی + مووچە
        $monthlyPurchaseCost = FuelPurchase::whereMonth('purchase_date', $thisMonth->month)
            ->whereYear('purchase_date', $thisMonth->year)
            ->sum('total_price');

        $monthlyExpenses = Expense::whereMonth('expense_date', $thisMonth->month)
            ->whereYear('expense_date', $thisMonth->year)
            ->sum('amount');

        $monthlySalaries = \App\Models\Salary::whereMonth('payment_date', $thisMonth->month)
            ->whereYear('payment_date', $thisMonth->year)
            ->sum('net_amount');

        $totalMonthlyExpenses = $monthlyPurchaseCost + $monthlyExpenses + $monthlySalaries;

        // ✅ دوێنێ vs ئەمڕۆ بۆ trend
        $todayRevenue = Sale::whereDate('sale_date', today())->sum('total_price');
        $yesterdayRevenue = Sale::whereDate('sale_date', today()->subDay())->sum('total_price');
        $revenueTrend = $yesterdayRevenue > 0
            ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1)
            : 0;

        // ✅ قاسە
        $cash = Cash::first();
        $cashBalance = $cash?->balance ?? 0;

        return [
            // ١. داهاتی ئەم مانگە
            Stat::make('داهاتی ئەم مانگە', $this->fmt($monthlySalesRevenue))
                ->description('کۆی فرۆشتنی مانگ ' . $thisMonth->format('m/Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($this->getLast7DaysRevenue()),

            // ٢. قازانجی خاڵص (داهات - نرخی کڕین)
            Stat::make('قازانجی ئەم مانگە', $this->fmt($monthlyProfit))
                ->description('بە کەمکردنی نرخی کڕین')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($monthlyProfit > 0 ? 'success' : 'danger'),

            // ٣. کۆی قازانجی گشتی
            Stat::make('قازانجی گشتی (هەموو کات)', $this->fmt($totalProfit))
                ->description('لیترێک = نرخ فرۆشتن - نرخ کڕین')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            // ٤. خەرجیەکانی ئەم مانگە
            Stat::make('خەرجیەکانی ئەم مانگە', $this->fmt($totalMonthlyExpenses))
                ->description(
                    'کڕین: ' . $this->fmt($monthlyPurchaseCost) .
                    ' | خەرجی: ' . $this->fmt($monthlyExpenses) .
                    ' | مووچە: ' . $this->fmt($monthlySalaries)
                )
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),

            // ٥. داهاتی ئەمڕۆ
            Stat::make('داهاتی ئەمڕۆ', $this->fmt($todayRevenue))
                ->description(
                    $revenueTrend >= 0
                        ? '⬆ ' . abs($revenueTrend) . '% زیاتر لە دوێنێ'
                        : '⬇ ' . abs($revenueTrend) . '% کەمتر لە دوێنێ'
                )
                ->descriptionIcon($revenueTrend >= 0 ? 'heroicon-m-arrow-up-circle' : 'heroicon-m-arrow-down-circle')
                ->color($revenueTrend >= 0 ? 'success' : 'danger'),

            // ٦. بڕی قاسە
            Stat::make('قاسەی ئێستا', $this->fmt($cashBalance))
                ->description('باڵانسی ئێستای قاسە')
                ->descriptionIcon('heroicon-m-building-library')
                ->color($cashBalance > 0 ? 'success' : 'danger'),
        ];
    }

    // ✅ هەفت ڕۆژی ڕابردوو بۆ چارت
    private function getLast7DaysRevenue(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = Sale::whereDate('sale_date', Carbon::now()->subDays($i))->sum('total_price') / 1000;
        }
        return $data;
    }

    // ✅ فۆرمەتکردنی پارە: ملیۆن یان هەزار
    private function fmt(float $amount): string
    {
        $amount = abs($amount);
        if ($amount >= 1_000_000) {
            return number_format($amount / 1_000_000, 2) . ' ملیۆن د.ع';
        }
        if ($amount >= 1_000) {
            return number_format($amount / 1_000, 1) . ' هەزار د.ع';
        }
        return number_format($amount) . ' د.ع';
    }
}
