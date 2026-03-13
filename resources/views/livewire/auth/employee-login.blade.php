<div dir="rtl" class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-indigo-50 to-purple-50">
    <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white shadow-2xl rounded-2xl">
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <img src="{{ asset('logo/logo.PNG') }}" alt="Logo" class="h-20 w-auto">
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">بەخێربێیت بۆ بەشی کارمەندان</h2>
            <p class="text-gray-600">تکایە بچۆرە ژوورەوە</p>
        </div>

        <form wire:submit="login" class="space-y-6">
            {{-- ئیمەیڵ --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">ئیمەیڵ</label>
                <input
                    wire:model="email"
                    id="email"
                    type="email"
                    class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-red-300 @enderror"
                    placeholder="employee@company.com"
                    required
                    autofocus
                >
                @error('email')
                    <span class="text-sm text-red-600 mt-1">{{ $message }}</span>
                @enderror
            </div>

            {{-- وشەی نهێنی --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">وشەی نهێنی</label>
                <input
                    wire:model="password"
                    id="password"
                    type="password"
                    class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('password') border-red-300 @enderror"
                    required
                >
                @error('password')
                    <span class="text-sm text-red-600 mt-1">{{ $message }}</span>
                @enderror
            </div>

            {{-- بیرم بێت --}}
            <div class="flex items-center">
                <input
                    wire:model="remember"
                    id="remember"
                    type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 ml-2"
                >
                <label for="remember" class="text-sm text-gray-900">منت لە بیر بێت</label>
            </div>

            {{-- دوگمە --}}
            <div>
                <button type="submit"
                    class="flex w-full justify-center items-center py-3 px-4 border border-transparent rounded-xl text-sm font-medium text-black bg-yellow-400 hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all duration-200"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="login">چوونەژوورەوە</span>
                    <span wire:loading wire:target="login">تکایە چاوەڕێ بکە...</span>
                </button>
            </div>
        </form>
    </div>
</div>
