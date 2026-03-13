<div dir="rtl">
    <!-- سەرچاوە -->
    <div class="bg-gradient-to-r from-red-600 to-pink-600 rounded-lg shadow-lg p-6 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3 rtl:space-x-reverse">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h2 class="text-2xl font-bold">بەڕێوەبردنی سزاکان</h2>
                    <p class="text-red-100">تۆمار و بەڕێوەبردنی سزای کارمەندان</p>
                </div>
            </div>
            <button wire:click="openModal" class="bg-white text-red-600 px-6 py-2 rounded-lg flex items-center space-x-2 rtl:space-x-reverse hover:bg-red-50 transition-colors duration-200 font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span>سزای نوێ</span>
            </button>
        </div>
    </div>

    <!-- کارتەکانی کورتە -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600 mb-1">کۆی سزای ئەم مانگە</p>
            <p class="text-xl font-bold text-red-600">{{ number_format($stats['total_this_month']) }} د.ع</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600 mb-1">کۆی سزای ئەمساڵ</p>
            <p class="text-xl font-bold text-orange-600">{{ number_format($stats['total_this_year']) }} د.ع</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600 mb-1">ژمارەی سزاکان</p>
            <p class="text-xl font-bold text-blue-600">{{ $stats['count_this_month'] }} سزا</p>
            <p class="text-xs text-gray-400">ئەم مانگە</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-600 mb-1">زیاترین سزا</p>
            <p class="text-xl font-bold text-purple-600">{{ $stats['most_penalized']?->name ?? '-' }}</p>
        </div>
    </div>

    <!-- تیبڵی سزاکان -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">#</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">کارمەند</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">بڕی سزا</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">ڕێکەوت</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">هۆکار</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">تێبینی</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">کردارەکان</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($penalties as $index => $penalty)
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $penalties->firstItem() + $index }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center ml-2">
                                    <span class="text-red-600 font-bold text-sm">{{ substr($penalty->employee->name, 0, 1) }}</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ $penalty->employee->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-sm font-bold">
                                {{ number_format($penalty->amount) }} د.ع
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $penalty->penalty_date->format('Y/m/d') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                            {{ $penalty->reason }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                            {{ $penalty->notes ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                <button wire:click="editPenalty({{ $penalty->id }})" class="text-blue-600 hover:text-blue-900">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button wire:click="deletePenalty({{ $penalty->id }})" wire:confirm="دڵنیایت لە سڕینەوەی ئەم سزایە؟" class="text-red-600 hover:text-red-900">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="text-gray-500 text-lg">هیچ سزایەک تۆمار نەکراوە</p>
                            <button wire:click="openModal" class="mt-4 text-red-600 hover:text-red-800">
                                یەکەم سزا تۆمار بکە
                            </button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- پەیجینەیشن -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $penalties->links() }}
        </div>
    </div>

    <!-- مۆدالی تۆمار/دەستکاری سزا -->
    @if($showModal)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50" wire:click="$set('showModal', false)"></div>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form wire:submit="save">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-2xl font-bold text-gray-900">
                                {{ $editingPenalty ? 'دەستکاری سزا' : 'تۆماری سزای نوێ' }}
                            </h3>
                            <button type="button" wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
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

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- کارمەند -->
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">کارمەند</label>
                                <select wire:model="employee_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500" required>
                                    <option value="">هەڵبژاردنی کارمەند</option>
                                    @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                                @error('employee_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- بڕی سزا -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">بڕی سزا (دینار)</label>
                                <input type="number" wire:model="amount" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500" required>
                                @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- ڕێکەوتی سزا -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">ڕێکەوتی سزا</label>
                                <input type="date" wire:model="penalty_date" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500" required>
                                @error('penalty_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- هۆکار -->
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">هۆکار</label>
                                <input type="text" wire:model="reason" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500" placeholder="هۆکاری سزا" required>
                                @error('reason') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- تێبینی -->
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">تێبینی</label>
                                <textarea wire:model="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500" placeholder="تێبینی زیادە..."></textarea>
                                @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ $editingPenalty ? 'نوێکردنەوە' : 'تۆمارکردن' }}
                        </button>
                        <button type="button" wire:click="$set('showModal', false)" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            ڕەتکردنەوە
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
