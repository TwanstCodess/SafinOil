<div dir="rtl">
    <!-- سەرچاوە -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg shadow-lg p-6 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3 rtl:space-x-reverse">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h2 class="text-2xl font-bold">مێژووی مووچەکان</h2>
                    <p class="text-indigo-100">تۆمارەکانی مووچەی کارمەندان</p>
                </div>
            </div>
            <div class="bg-white bg-opacity-20 rounded-lg px-4 py-2">
                <span class="text-sm opacity-75">کۆی گشتی:</span>
                <span class="text-xl font-bold mr-2">{{ number_format($stats['total']) }} د.ع</span>
            </div>
        </div>
    </div>

    <!-- کارتەکانی کورتە -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600 mb-1">کۆی مووچە</p>
            <p class="text-xl font-bold text-indigo-600">{{ number_format($stats['total']) }} د.ع</p>
            <p class="text-xs text-gray-400 mt-1">{{ $stats['count'] }} تۆمار</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600 mb-1">تێکڕای مووچە</p>
            <p class="text-xl font-bold text-green-600">{{ number_format($stats['avg']) }} د.ع</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600 mb-1">کۆی سزا</p>
            <p class="text-xl font-bold text-red-600">{{ number_format($stats['deductions_total']) }} د.ع</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600 mb-1">کۆی زیادەکراو</p>
            <p class="text-xl font-bold text-blue-600">{{ number_format($stats['additions_total']) }} د.ع</p>
        </div>
    </div>

    <!-- فلترەکان -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">کارمەند</label>
                <select wire:model.live="employee_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                    <option value="">هەموو کارمەندان</option>
                    @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ساڵ</label>
                <select wire:model.live="year" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                    <option value="">هەموو ساڵەکان</option>
                    @foreach($years as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">مانگ</label>
                <select wire:model.live="month" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                    <option value="">هەموو مانگەکان</option>
                    @foreach($monthNames as $num => $name)
                    <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">گەڕان</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ناوی کارمەند..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
    </div>

    <!-- تیبڵی مێژووی مووچە -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">#</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">کارمەند</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">مانگ/ساڵ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">مووچە</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">سزا</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">زیادەکراو</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">مووچەی پاک</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">ڕێکەوتی پێدان</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">کردارەکان</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($salaries as $index => $salary)
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $salaries->firstItem() + $index }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center ml-2">
                                    <span class="text-indigo-600 font-bold text-sm">{{ substr($salary->employee->name, 0, 1) }}</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ $salary->employee->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs">
                                {{ $monthNames[$salary->month] }} {{ $salary->year }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($salary->base_amount) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                            {{ number_format($salary->deductions) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600">
                            {{ number_format($salary->additions) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                            {{ number_format($salary->net_amount) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $salary->payment_date->format('Y/m/d') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button wire:click="viewDetails({{ $salary->id }})" class="text-indigo-600 hover:text-indigo-900">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="text-gray-500 text-lg">هیچ مووچەیەک نەدۆزرایەوە</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- پەیجینەیشن -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $salaries->links() }}
        </div>
    </div>

    <!-- مۆدالی وردەکاری -->
    @if($showDetails && $selectedSalary)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50" wire:click="$set('showDetails', false)"></div>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">وردەکاری مووچە</h3>
                        <button wire:click="$set('showDetails', false)" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">ناوی کارمەند</p>
                                <p class="text-lg font-bold">{{ $selectedSalary->employee->name }}</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">مانگ/ساڵ</p>
                                <p class="text-lg font-bold">{{ $monthNames[$selectedSalary->month] }} {{ $selectedSalary->year }}</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">بڕی مووچە</p>
                                <p class="text-lg font-bold">{{ number_format($selectedSalary->base_amount) }} د.ع</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">بڕی سزا</p>
                                <p class="text-lg font-bold text-red-600">{{ number_format($selectedSalary->deductions) }} د.ع</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">زیادەکراو</p>
                                <p class="text-lg font-bold text-blue-600">{{ number_format($selectedSalary->additions) }} د.ع</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">مووچەی پاک</p>
                                <p class="text-lg font-bold text-green-600">{{ number_format($selectedSalary->net_amount) }} د.ع</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">ڕێکەوتی پێدان</p>
                                <p class="text-lg font-bold">{{ $selectedSalary->payment_date->format('Y/m/d') }}</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600 mb-1">تێبینی</p>
                                <p class="text-lg">{{ $selectedSalary->notes ?? 'نییە' }}</p>
                            </div>
                        </div>

                        <!-- کورتەی کارمەند -->
                        @php $summary = $this->employeeSummary; @endphp
                        @if($summary)
                        <div class="mt-6 border-t border-gray-200 pt-4">
                            <h4 class="text-lg font-bold text-gray-800 mb-4">کورتەی کارمەند</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-indigo-50 p-3 rounded-lg text-center">
                                    <p class="text-xs text-indigo-600 mb-1">کۆی مووچە</p>
                                    <p class="text-sm font-bold">{{ number_format($summary['total_paid']) }} د.ع</p>
                                </div>
                                <div class="bg-red-50 p-3 rounded-lg text-center">
                                    <p class="text-xs text-red-600 mb-1">کۆی سزا</p>
                                    <p class="text-sm font-bold">{{ number_format($summary['total_deductions']) }} د.ع</p>
                                </div>
                                <div class="bg-blue-50 p-3 rounded-lg text-center">
                                    <p class="text-xs text-blue-600 mb-1">کۆی زیادەکراو</p>
                                    <p class="text-sm font-bold">{{ number_format($summary['total_additions']) }} د.ع</p>
                                </div>
                                <div class="bg-green-50 p-3 rounded-lg text-center">
                                    <p class="text-xs text-green-600 mb-1">ژمارەی مانگەکان</p>
                                    <p class="text-sm font-bold">{{ $summary['payments_count'] }} مانگ</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" wire:click="$set('showDetails', false)" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        داخستن
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
