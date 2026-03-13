<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Favicon (ئایکۆنی ناونیشان) -->
        <link rel="icon" type="image/png" href="{{ asset('logo/logo.PNG') }}">
        <link rel="shortcut icon" href="{{ asset('logo/logo.PNG') }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Livewire Styles -->
        @livewireStyles

        <!-- RTL Styles -->
        <style>
            body {
                font-family: 'IBM Plex Sans Arabic', sans-serif;
            }
            /* ڕاستکردنەوەی بۆشاییەکان بۆ RTL */
            .space-x-8 > :not([hidden]) ~ :not([hidden]) {
                margin-right: 2rem;
                margin-left: 0;
            }
            .sm\:ms-10 {
                margin-right: 2.5rem;
                margin-left: 0;
            }
            .ms-1 {
                margin-right: 0.25rem;
                margin-left: 0;
            }
            .ms-6 {
                margin-right: 1.5rem;
                margin-left: 0;
            }
            .me-2 {
                margin-left: 0.5rem;
                margin-right: 0;
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- Livewire Scripts -->
        @livewireScripts
    </body>
</html>
