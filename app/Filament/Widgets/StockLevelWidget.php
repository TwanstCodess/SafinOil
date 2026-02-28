<?php
// app/Filament/Widgets/StockLevelWidget.php
namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Widgets\ChartWidget;

class StockLevelWidget extends ChartWidget
{
    protected static ?string $heading = 'ئاستی کۆگا (لیتر)';
    protected static ?int $sort = 6;

    protected function getData(): array
    {
        $categories = Category::with('type')->get();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($categories as $category) {
            $labels[] = $category->name;
            $data[] = $category->stock_liters;

            $colors[] = match($category->type?->key) {
                'fuel' => '#f59e0b',
                'oil' => '#10b981',
                'gas' => '#3b82f6',
                default => '#6b7280',
            };
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'لیتر',
                    ],
                ],
            ],
        ];
    }
}
