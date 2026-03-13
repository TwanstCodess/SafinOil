<div dir="rtl">
    <!-- کارتە سەرەکیەکان -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach($cards as $card)
        <div class="bg-white rounded-lg shadow-md p-6 border-r-4 border-{{ $card['color'] }}-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">{{ $card['title'] }}</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $card['value'] }}</p>
                </div>
                <div class="bg-{{ $card['color'] }}-100 p-3 rounded-full">
                    <svg class="w-8 h-8 text-{{ $card['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($card['icon'] == 'users')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        @elseif($card['icon'] == 'currency-dollar')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        @elseif($card['icon'] == 'chart-bar')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        @endif
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-green-600 font-medium ml-2">{{ $card['change'] }}</span>
                <span class="text-gray-500">{{ $card['period'] }}</span>
            </div>
        </div>
        @endforeach
    </div>

    <!-- سەرچی و فلتەر -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <h2 class="text-xl font-bold text-gray-800">لیستی کارمەندان و موچەکان</h2>
            </div>

            <div class="flex flex-wrap gap-3">
                <!-- فلتری گەڕان -->
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="گەڕان بە ناوی کارمەند..."
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- فلتری دۆخ -->
                <select wire:model.live="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="">هەموو کارمەندان</option>
                    <option value="active">چالاک</option>
                    <option value="inactive">ناچالاک</option>
                </select>
            </div>
        </div>
    </div>

    <!-- تیبڵی کارمەندان -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button wire:click="sortBy('name')" class="flex items-center space-x-1 rtl:space-x-reverse">
                                <span>ناوی کارمەند</span>
                                @if($sortField === 'name')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        @endif
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            پلە/پیشە
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button wire:click="sortBy('salary')" class="flex items-center space-x-1 rtl:space-x-reverse">
                                <span>مووچەی بنەڕەتی</span>
                                @if($sortField === 'salary')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        @endif
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            کۆی موچەی وەرگیراو
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ژمارەی مانگەکان
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            دوایین مووچە
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            دۆخ
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            کردارەکان
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($employees as $employee)
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ substr($employee->name, 0, 1) }}
                                </div>
                                <div class="mr-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $employee->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $employee->phone ?? 'ژمارە نییە' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $employee->position ?? 'دیاری نەکراو' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                            {{ number_format($employee->salary) }} د.ع
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-bold">
                            {{ number_format($employee->salaries_sum_net_amount ?? 0) }} د.ع
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                {{ $employee->salaries_count ?? 0 }} مانگ
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($employee->salaries->first())
                                {{ $employee->salaries->first()->payment_date->format('Y/m/d') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($employee->is_active)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    چالاک
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    ناچالاک
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                <button wire:click="viewEmployee({{ $employee->id }})" class="text-indigo-600 hover:text-indigo-900">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <a href="{{ route('payroll.employee.detail', $employee->id) }}" class="text-blue-600 hover:text-blue-900">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="text-gray-500 text-lg mb-2">هیچ کارمەندێک نەدۆزرایەوە</p>
                            <p class="text-gray-400">تکایە کارمەندێک زیاد بکە یان فلترەکان بگۆڕە</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- پەیجینەیشن -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $employees->links() }}
        </div>
    </div>

    <!-- مۆدالی بینینی وردەکاری کارمەند -->
    @if($showEmployeeModal && $selectedEmployee)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50" wire:click="$set('showEmployeeModal', false)"></div>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">وردەکاری کارمەند: {{ $selectedEmployee->name }}</h3>
                        <button wire:click="$set('showEmployeeModal', false)" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- کارتەکانی کورتە -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg">
                            <p class="text-sm text-blue-600 mb-1">کۆی موچەی وەرگیراو</p>
                            <p class="text-xl font-bold text-blue-800">{{ number_format($selectedEmployee->salaries_sum_net_amount ?? 0) }} د.ع</p>
                        </div>
                        <div class="bg-gradient-to-br from-red-50 to-red-100 p-4 rounded-lg">
                            <p class="text-sm text-red-600 mb-1">کۆی سزا</p>
                            <p class="text-xl font-bold text-red-800">{{ number_format($selectedEmployee->penalties_sum_amount ?? 0) }} د.ع</p>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg">
                            <p class="text-sm text-green-600 mb-1">ژمارەی مانگەکان</p>
                            <p class="text-xl font-bold text-green-800">{{ $selectedEmployee->salaries_count ?? 0 }} مانگ</p>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg">
                            <p class="text-sm text-purple-600 mb-1">دوایین مووچە</p>
                            <p class="text-xl font-bold text-purple-800">{{ $selectedEmployee->salaries->first()?->payment_date?->format('Y/m/d') ?? '-' }}</p>
                        </div>
                    </div>

                    <!-- مێژووی مووچە -->
                    <h4 class="text-lg font-bold text-gray-800 mb-4">مێژووی مووچە (١٢ مانگی ڕابردوو)</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">مانگ</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">بڕی مووچە</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">سزا</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">مووچەی پاک</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">ڕێکەوت</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($selectedEmployee->salaries as $salary)
                                <tr>
                                    <td class="px-4 py-2">{{ $monthNames[$salary->month] ?? $salary->month }} {{ $salary->year }}</td>
                                    <td class="px-4 py-2">{{ number_format($salary->base_amount) }}</td>
                                    <td class="px-4 py-2 text-red-600">{{ number_format($salary->deductions) }}</td>
                                    <td class="px-4 py-2 text-green-600 font-bold">{{ number_format($salary->net_amount) }}</td>
                                    <td class="px-4 py-2">{{ $salary->payment_date->format('Y/m/d') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
