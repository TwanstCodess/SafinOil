<?php
// app/Filament/Widgets/SalesPurchasesChartWidget.php
namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Models\FuelPurchase;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class SalesPurchasesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'داهات، خەرجی و قازانج (٣٠ ڕۆژی ڕابردوو)';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $sales     = [];
        $purchases = [];
        $profits   = [];
        $labels    = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('m/d');

            // داهاتی ئەو ڕۆژە
            $daySales = Sale::whereDate('sale_date', $date)->sum('total_price');

            // خەرجی کڕینی ئەو ڕۆژە
            $dayPurchases = FuelPurchase::whereDate('purchase_date', $date)->sum('total_price');

            // ✅ قازانجی ئەو ڕۆژە = liters × (فرۆش - کڕین)
            $dayProfit = Sale::join('categories', 'sales.category_id', '=', 'categories.id')
                ->whereDate('sales.sale_date', $date)
                ->selectRaw('SUM(sales.liters * (sales.price_per_liter - categories.purchase_price)) as profit')
                ->value('profit') ?? 0;

            $sales[]     = round($daySales / 1000, 1);
            $purchases[] = round($dayPurchases / 1000, 1);
            $profits[]   = round($dayProfit / 1000, 1);
        }

        return [
            'datasets' => [
                [
                    'label'           => 'داهات (هەزار د.ع)',
                    'data'            => $sales,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'borderColor'     => '#10b981',
                    'fill'            => true,
                    'tension'         => 0.3,
                ],
                [
                    'label'           => 'کڕین (هەزار د.ع)',
                    'data'            => $purchases,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'borderColor'     => '#f59e0b',
                    'fill'            => true,
                    'tension'         => 0.3,
                ],
                [
                    'label'           => 'قازانج (هەزار د.ع)',
                    'data'            => $profits,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                    'borderColor'     => '#3b82f6',
                    'fill'            => true,
                    'tension'         => 0.3,
                    'borderDash'      => [5, 5],
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
                    'title' => ['display' => true, 'text' => 'هەزار دینار'],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [],
                ],
            ],
        ];
    }
}
