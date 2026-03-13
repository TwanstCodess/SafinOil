<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Safin Oil') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('logo/logo.PNG') }}">
        <link rel="shortcut icon" href="{{ asset('logo/logo.PNG') }}">
        <link rel="apple-touch-icon" href="{{ asset('logo/logo.PNG') }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Livewire Styles -->
        @livewireStyles

        <!-- Custom Styles -->
        <style>
            body {
                font-family: 'IBM Plex Sans Arabic', sans-serif;
            }

            /* RTL Fixes */
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

            .-me-2 {
                margin-left: -0.5rem;
                margin-right: 0;
            }

            /* Sidebar Transitions */
            .transition-all {
                transition-property: all;
                transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
                transition-duration: 300ms;
            }

            /* Custom Scrollbar */
            ::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }

            ::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 10px;
            }

            ::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <div class="min-h-screen">
            @include('layouts.navigation')

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- Livewire Scripts -->
        @livewireScripts

        <!-- Alpine.js -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </body>
</html>
