<div dir="rtl">
    <form wire:submit="login" class="space-y-4">
        {{--  بەشی ئیمەیڵ --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">ئیمەیڵ</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
                autofocus
            >
            @error('email')
                <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        {{--  بەشی وشەی نهێنی --}}
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">وشەی نهێنی</label>
            <input
                wire:model="password"
                id="password"
                type="password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
            @error('password')
                <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>

        {{--  بیرم بێت --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input
                    wire:model="remember"
                    id="remember"
                    type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                ><span class="m-1"></span>
                <label for="remember" class="ml-2 block text-sm text-gray-900"> منت لە بیر بێت  </label>
            </div>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                    وشەی نهێنیت لەبیرە؟
                </a>
            @endif
        </div>

        {{--  دوگمەی چوونەژوورەوە --}}
        <div>
            <button type="submit"
                class="flex w-full justify-center rounded-md border border-transparent bg-yellow-400 px-4 py-2 text-sm font-medium text-black shadow-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                چوونەژوورەوە
            </button>
        </div>
    </form>
</div>
