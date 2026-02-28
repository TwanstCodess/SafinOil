<?php
// app/Filament/Widgets/ExpenseBreakdownWidget.php
namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Salary;
use App\Models\FuelPurchase;
use Filament\Widgets\PieChartWidget;
use Carbon\Carbon;

class ExpenseBreakdownWidget extends PieChartWidget
{
    protected static ?string $heading = 'پێکهاتەی خەرجییەکان (ئەم مانگە)';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $expenses = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])->sum('amount');
        $salaries = Salary::whereBetween('payment_date', [$startOfMonth, $endOfMonth])->sum('net_amount');
        $purchases = FuelPurchase::whereBetween('purchase_date', [$startOfMonth, $endOfMonth])->sum('total_price');

        $total = $expenses + $salaries + $purchases;

        return [
            'datasets' => [
                [
                    'data' => [
                        $expenses / 1000,
                        $salaries / 1000,
                        $purchases / 1000,
                    ],
                    'backgroundColor' => [
                        '#ef4444',
                        '#f97316',
                        '#eab308',
                    ],
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => [
                'خەرجی گشتی (' . number_format($expenses / 1000, 1) . ' هەزار)',
                'مووچە (' . number_format($salaries / 1000, 1) . ' هەزار)',
                'کڕین (' . number_format($purchases / 1000, 1) . ' هەزار)',
            ],
        ];
    }
}
