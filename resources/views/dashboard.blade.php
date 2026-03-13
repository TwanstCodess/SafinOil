<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('داشبۆرد') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Sales Card -->
                <div class="bg-white overflow-hidden shadow-lg rounded-2xl hover:shadow-xl transition-shadow duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="bg-indigo-100 rounded-xl p-3">
                                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-green-600 bg-green-100 px-3 py-1 rounded-full">+12.5%</span>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-1">١٢٬٤٥٠٬٠٠٠ د.ع</h3>
                        <p class="text-gray-600">کۆی گشتی فرۆشتن</p>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">بەراورد بە مانگی ڕابردوو</span>
                                <span class="text-green-600 font-medium">↑ ٢٫١٪</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Liters Card -->
                <div class="bg-white overflow-hidden shadow-lg rounded-2xl hover:shadow-xl transition-shadow duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="bg-blue-100 rounded-xl p-3">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-1">٤٥٬٦٧٨ لیتر</h3>
                        <p class="text-gray-600">کۆی گشتی فرۆشراو</p>
                    </div>
                </div>

                <!-- Today Sales Card -->
                <div class="bg-white overflow-hidden shadow-lg rounded-2xl hover:shadow-xl transition-shadow duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="bg-green-100 rounded-xl p-3">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-1">٢٬٣٤٠٬٠٠٠ د.ع</h3>
                        <p class="text-gray-600">فرۆشتنی ئەمڕۆ</p>
                    </div>
                </div>

                <!-- Active Shifts Card -->
                <div class="bg-white overflow-hidden shadow-lg rounded-2xl hover:shadow-xl transition-shadow duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="bg-purple-100 rounded-xl p-3">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-3xl font-bold text-gray-900 mb-1">٢ شەفت</h3>
                        <p class="text-gray-600">شەفتی چالاک</p>
                        <div class="mt-4 flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            <span class="text-sm text-gray-500">بەیانی و ئێوارە چالاکە</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Sales Chart -->
                <div class="bg-white overflow-hidden shadow-lg rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-900">ڕێژەی فرۆشتن</h3>
                        <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option>٧ ڕۆژی ڕابردوو</option>
                            <option>٣٠ ڕۆژی ڕابردوو</option>
                            <option>٣ مانگی ڕابردوو</option>
                        </select>
                    </div>
                    <div class="h-64 flex items-center justify-center bg-gray-50 rounded-xl">
                        <span class="text-gray-400">هێڵکاری فرۆشتن لێرەدا پیشان دەدرێت</span>
                    </div>
                </div>

                <!-- Categories Distribution -->
                <div class="bg-white overflow-hidden shadow-lg rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-900">دابەشبوونی فرۆشتن بەپێی کاتیگۆری</h3>
                    </div>
                    <div class="h-64 flex items-center justify-center bg-gray-50 rounded-xl">
                        <span class="text-gray-400">هێڵکاری بازنەیی لێرەدا پیشان دەدرێت</span>
                    </div>
                </div>
            </div>

            <!-- Recent Quick Sales -->
            <div class="bg-white overflow-hidden shadow-lg rounded-2xl">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">دوایین فرۆشی خێرا</h3>
                        <a href="{{ url('/admin/quick-sales') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center">
                            بینینی هەموو
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    @php
                        $recentSales = App\Models\QuickSale::with(['creator', 'closer'])
                            ->latest()
                            ->take(5)
                            ->get();
                    @endphp

                    @forelse($recentSales as $sale)
                        <div class="px-6 py-4 hover:bg-gray-50 transition-colors duration-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4 rtl:space-x-reverse">
                                    <div class="flex-shrink-0">
                                        @if($sale->shift === 'morning')
                                            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                                <span class="text-yellow-600 text-xl">🌅</span>
                                            </div>
                                        @else
                                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                                <span class="text-indigo-600 text-xl">🌙</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-bold text-gray-900">{{ $sale->shift_name }}</h4>
                                            <span class="px-2 py-1 text-xs rounded-full {{ $sale->status === 'open' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                                {{ $sale->status_name }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500">{{ $sale->sale_date->format('Y/m/d') }} - لەلایەن {{ $sale->creator?->name ?? 'سیستەم' }}</p>
                                    </div>
                                </div>
                                <div class="text-left">
                                    <div class="font-bold text-gray-900">{{ number_format($sale->total_amount) }} د.ع</div>
                                    <div class="text-sm text-gray-500">{{ number_format($sale->total_liters * 2) }} لیتر</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4M12 4v16" />
                            </svg>
                            <h4 class="text-lg font-bold text-gray-700 mb-2">هیچ فرۆشی خێرایەک نییە</h4>
                            <p class="text-gray-500">یەکەم تۆماری فرۆشی خێرا دروست بکە</p>
                            <a href="{{ url('/admin/quick-sales/create') }}" class="inline-block mt-4 px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200">
                                دروستکردنی فرۆشی خێرا
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
