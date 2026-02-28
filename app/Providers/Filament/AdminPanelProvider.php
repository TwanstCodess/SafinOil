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

use Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage;
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

            // **زیادکردنی پروفایل بۆ مێنوی بەکارهێنەر**
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('پڕۆفایلی من')
                    ->url(fn (): string => EditProfilePage::getUrl())
                    ->icon('heroicon-m-user-circle'),

                // ئارادی: جیاکەرەوە
                'separator' => MenuItem::make()
                    ->label('')
                    ->url('#')
                    ->visible(false),
            ])

            // **زیادکردنی پێکهاتەکانی Edit Profile**
            ->plugins([
                \Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin::make()
                    ->slug('my-profile') // URLی پڕۆفایل
                    ->setTitle('پڕۆفایلی من')
                    ->setNavigationLabel('پڕۆفایلی من')
                    ->setNavigationGroup('بەشی کەسی')
                    ->setIcon('heroicon-o-user')
                    ->setSort(10)
                    ->shouldRegisterNavigation(false) // لە مێنوی لاتەرەدا پیشان مەدە (تەنها لە مێنوی سەرەوە)
                    ->shouldShowDeleteAccountForm(false) // پیشاندانی فۆرمی سڕینەوەی ئەکاونت
                    ->shouldShowBrowserSessionsForm(false) // پیشاندانی فۆرمی سێشنەکان
                    ->shouldShowAvatarForm(false), // پیشاندانی فۆرمی وێنەی پڕۆفایل
            ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
