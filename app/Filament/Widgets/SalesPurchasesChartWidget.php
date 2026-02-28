<?php
// app/Filament/Widgets/SalesPurchasesChartWidget.php
namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Models\FuelPurchase;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class SalesPurchasesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'ڕێژەی فرۆشتن و کڕین (٣٠ ڕۆژی ڕابردوو)';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('m/d');

            $sales = Sale::whereDate('sale_date', $date)->sum('total_price');
            $purchases = FuelPurchase::whereDate('purchase_date', $date)->sum('total_price');

            $data['sales'][] = $sales / 1000; // بە هەزاران
            $data['purchases'][] = $purchases / 1000;
        }

        return [
            'datasets' => [
                [
                    'label' => 'فرۆشتن (هەزار دینار)',
                    'data' => $data['sales'],
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#10b981',
                ],
                [
                    'label' => 'کڕین (هەزار دینار)',
                    'data' => $data['purchases'],
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#f59e0b',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'هەزار دینار',
                    ],
                ],
            ],
        ];
    }
}
