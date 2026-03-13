<div dir="rtl">
    <!-- سەرچاوە -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2 class="text-2xl font-bold text-gray-800">تۆماری مووچە</h2>
            </div>
            <button wire:click="openPaymentModal" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg flex items-center space-x-2 rtl:space-x-reverse transition-colors duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span>تۆماری مووچەی نوێ</span>
            </button>
        </div>
    </div>

    <!-- فلتری مانگ و ساڵ -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-wrap gap-4 items-center">
            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                <label class="text-gray-700 font-medium">مانگ:</label>
                <select wire:model.live="selectedMonth" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                    <option value="1">ڕێبەندان</option>
                    <option value="2">ڕەشەمە</option>
                    <option value="3">نەورۆز</option>
                    <option value="4">گوڵان</option>
                    <option value="5">جۆزەردان</option>
                    <option value="6">پووشپەڕ</option>
                    <option value="7">گەلاوێژ</option>
                    <option value="8">خەرمانان</option>
                    <option value="9">ڕەزبەر</option>
                    <option value="10">گەڵاڕێزان</option>
                    <option value="11">سەرماوەز</option>
                    <option value="12">بەفرانبار</option>
                </select>
            </div>
            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                <label class="text-gray-700 font-medium">ساڵ:</label>
                <select wire:model.live="selectedYear" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                    @for($y = now()->year; $y >= 2020; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </div>
    </div>

    <!-- دوو تیبڵ: مووچە نەدراوەکان و مووچە دراوەکان -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- کارمەندانی مووچە نەدراو -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-yellow-50 px-6 py-4 border-b border-yellow-200">
                <h3 class="text-lg font-bold text-yellow-800 flex items-center">
                    <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    مووچە نەدراوەکان ({{ count($pendingEmployees) }})
                </h3>
            </div>
            <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                @forelse($pendingEmployees as $employee)
                <div class="px-6 py-4 hover:bg-gray-50 transition-colors duration-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center ml-3">
                                <span class="text-indigo-600 font-bold">{{ substr($employee->name, 0, 1) }}</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900">{{ $employee->name }}</h4>
                                <p class="text-sm text-gray-500">{{ $employee->position ?? 'کارمەند' }}</p>
                            </div>
                        </div>
                        <div class="text-left">
                            <div class="font-bold text-gray-900">{{ number_format($employee->salary) }} د.ع</div>
                            <button wire:click="openPaymentModal({{ $employee->id }})" class="text-sm text-indigo-600 hover:text-indigo-800 mt-1">
                <span class="flex items-center">
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    زیادکردن
                </span>
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-12 text-center">
                    <svg class="w-12 h-12 text-green-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-gray-500">هەموو کارمەندان مووچەی ئەم مانگەی وەرگرتووە</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- کارمەندانی مووچە دراو -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-green-50 px-6 py-4 border-b border-green-200">
                <h3 class="text-lg font-bold text-green-800 flex items-center">
                    <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    مووچە دراوەکان ({{ count($paidEmployees) }})
                </h3>
            </div>
            <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                @forelse($paidEmployees as $employee)
                @php $salary = $employee->salaries->first(); @endphp
                <div class="px-6 py-4 hover:bg-gray-50 transition-colors duration-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center ml-3">
                                <span class="text-green-600 font-bold">{{ substr($employee->name, 0, 1) }}</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900">{{ $employee->name }}</h4>
                                <p class="text-sm text-gray-500">{{ $salary->payment_date->format('Y/m/d') }}</p>
                            </div>
                        </div>
                        <div class="text-left">
                            <div class="font-bold text-green-600">{{ number_format($salary->net_amount) }} د.ع</div>
                            @if($salary->deductions > 0)
                            <div class="text-xs text-red-500">سزا: {{ number_format($salary->deductions) }} د.ع</div>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-12 text-center">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p class="text-gray-500">هیچ مووچەیەک تۆمار نەکراوە</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- مۆدالی تۆماری مووچە -->
    @if($showPaymentModal)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50" wire:click="$set('showPaymentModal', false)"></div>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form wire:submit="savePayment">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-2xl font-bold text-gray-900">تۆماری مووچەی نوێ</h3>
                            <button type="button" wire:click="$set('showPaymentModal', false)" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        @if(session()->has('message'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                            {{ session('message') }}
                        </div>
                        @endif

                        @if(session()->has('error'))
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                            {{ session('error') }}
                        </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- کارمەند -->
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">کارمەند</label>
                                <select wire:model.live="employee_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" required>
                                    <option value="">هەڵبژاردنی کارمەند</option>
                                    @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }} - {{ number_format($employee->salary) }} د.ع</option>
                                    @endforeach
                                </select>
                                @error('employee_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- بڕی مووچە -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">بڕی مووچە</label>
                                <input type="number" wire:model.live="amount" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" required>
                                @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- سزا -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">بڕی سزا</label>
                                <input type="number" wire:model.live="deductions" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                                @error('deductions') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- زیادەکراو -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">زیادەکراو</label>
                                <input type="number" wire:model.live="additions" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                                @error('additions') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- مووچەی پاک -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">مووچەی پاک</label>
                                <input type="number" wire:model="net_amount" class="w-full border border-gray-300 bg-gray-50 rounded-lg px-4 py-2" readonly>
                            </div>

                            <!-- مانگ -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">مانگ</label>
                                <select wire:model="month" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" required>
                                    @foreach($monthNames as $num => $name)
                                    <option value="{{ $num }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('month') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- ساڵ -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">ساڵ</label>
                                <input type="number" wire:model="year" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" required>
                                @error('year') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- ڕێکەوتی پێدان -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">ڕێکەوتی پێدان</label>
                                <input type="date" wire:model="payment_date" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" required>
                                @error('payment_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- تێبینی -->
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">تێبینی</label>
                                <textarea wire:model="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"></textarea>
                                @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            تۆمارکردن
                        </button>
                        <button type="button" wire:click="$set('showPaymentModal', false)" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            ڕەتکردنەوە
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
