<?php
// app/Providers/Filament/AdminPanelProvider.php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\MenuItem;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->favicon(asset('logo/logo.PNG'))
            ->darkModeBrandLogo(asset('logo/logo.PNG'))
            ->brandLogo(asset('logo/logo.PNG'))
            ->font('IBM Plex Sans Arabic')
            ->brandLogoHeight('55px')
            ->maxContentWidth('full')
            ->spa()
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Orange,
            ])

            // مێنوی بەکارهێنەر
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('پڕۆفایلی من')
                    ->url(fn (): string => \Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage::getUrl())
                    ->icon('heroicon-m-user-circle'),
            ])

            // پلاگینەکان
            ->plugins([
                \Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin::make()
                    ->slug('my-profile')
                    ->setTitle('پڕۆفایلی من')
                    ->setNavigationLabel('پڕۆفایلی من')
                    ->setIcon('heroicon-o-user')
                    ->shouldRegisterNavigation(false)
                    ->shouldShowDeleteAccountForm(false)
                    ->shouldShowBrowserSessionsForm(false)
                    ->shouldShowAvatarForm(false),
            ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])

            // **ویجێتەکان**
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\FinancialOverviewWidget::class,
                \App\Filament\Widgets\SalesPurchasesChartWidget::class,
                \App\Filament\Widgets\ExpenseBreakdownWidget::class,
                \App\Filament\Widgets\CreditStatusWidget::class,
                \App\Filament\Widgets\StockLevelWidget::class,
                \App\Filament\Widgets\EmployeeStatsWidget::class,
                \App\Filament\Widgets\RecentTransactionsWidget::class,
            ])

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
