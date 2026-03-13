<?php
// app/Filament/Widgets/EmployeeStatsWidget.php
namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\Salary;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmployeeStatsWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected function getStats(): array
    {
        $activeEmployees = Employee::where('is_active', true)->count();
        $totalEmployees  = Employee::count();

        $thisMonthSalaries = Salary::whereMonth('payment_date', Carbon::now()->month)
            ->whereYear('payment_date', Carbon::now()->year)
            ->sum('net_amount');

        $avgSalary = $activeEmployees > 0 ? $thisMonthSalaries / $activeEmployees : 0;

        return [
            Stat::make('کارمەندانی چالاک', $activeEmployees . ' لە ' . $totalEmployees)
                ->description('ڕێژەی کارمەندان')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('کۆی مووچەی ئەم مانگە', $this->fmt($thisMonthSalaries))
                ->description('مووچەی مانگ ' . Carbon::now()->format('m/Y'))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),

            Stat::make('تێکڕای مووچە', $this->fmt($avgSalary))
                ->description('بۆ هەر کارمەندێک')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
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
