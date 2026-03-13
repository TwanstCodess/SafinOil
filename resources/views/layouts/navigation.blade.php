<nav x-data="{ open: false, sidebarOpen: true }" class="bg-white border-b border-gray-100">
    <!-- Desktop Navigation with Sidebar -->
    <div class="hidden sm:block">
        <!-- Top Bar -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Left side -->
                    <div class="flex items-center">
                        <!-- Sidebar Toggle Button -->
                        <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg hover:bg-white/10 transition-colors duration-200 ml-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <template x-if="!sidebarOpen">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </template>
                                <template x-if="sidebarOpen">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </template>
                            </svg>
                        </button>

                        <!-- Logo -->
                        <a href="{{ route('dashboard') }}" class="flex items-center">
                            <img src="{{ asset('logo/logo.PNG') }}" alt="Safin Oil" class="h-10 w-auto">
                            <span class="mr-2 font-bold text-xl">Safin Oil</span>
                        </a>
                    </div>

                    <!-- Right side -->
                    <div class="flex items-center space-x-4 rtl:space-x-reverse">
                        <!-- Notifications -->
                        <button class="p-2 rounded-lg hover:bg-white/10 transition-colors duration-200 relative">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>

                        <!-- User Dropdown -->
                        <div class="relative" x-data="{ dropdownOpen: false }">
                            <button @click="dropdownOpen = !dropdownOpen" class="flex items-center space-x-3 rtl:space-x-reverse bg-white/10 rounded-lg px-4 py-2 hover:bg-white/20 transition-colors duration-200">
                                <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="dropdownOpen" @click.away="dropdownOpen = false" class="absolute left-0 mt-2 w-48 bg-white rounded-lg shadow-xl py-1 z-50" style="display: none;">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-gray-800 hover:bg-indigo-50 hover:text-indigo-600 transition-colors duration-200">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {{ __('پڕۆفایل') }}
                                    </div>
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-right px-4 py-2 text-gray-800 hover:bg-red-50 hover:text-red-600 transition-colors duration-200">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            {{ __('چوونە دەرەوە') }}
                                        </div>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar and Main Content Wrapper -->
        <div class="flex">
            <!-- Sidebar -->
            <div x-show="sidebarOpen" x-transition:enter="transition-transform duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition-transform duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="w-64 bg-white shadow-xl min-h-screen border-l border-gray-200 fixed right-0 top-16 z-40">
                <div class="py-6 px-4">
                    <!-- User Info -->
                    <div class="mb-6 pb-4 border-b border-gray-200">
                        <div class="flex items-center space-x-3 rtl:space-x-reverse">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-xl">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="font-bold text-gray-800">{{ Auth::user()->name }}</div>
                                <div class="text-sm text-gray-500">{{ Auth::user()->email }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Links -->
                    <div class="space-y-2">
                        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200 group {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-600 border-r-4 border-indigo-600' : '' }}">
                            <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="font-medium">داشبۆرد</span>
                        </a>

                        <a href="{{ url('/admin/quick-sales') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200 group {{ request()->is('admin/quick-sales*') ? 'bg-indigo-50 text-indigo-600 border-r-4 border-indigo-600' : '' }}">
                            <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span class="font-medium">فرۆشی خێرا</span>
                            <span class="mr-auto bg-yellow-100 text-yellow-600 text-xs px-2 py-1 rounded-full">نوێ</span>
                        </a>

                        <div class="pt-4 mt-4 border-t border-gray-200">
                            <div class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">کەرەستەکان</div>

                            <a href="{{ url('/admin/categories') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200 group">
                                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l5 5a2 2 0 01.586 1.414V19a2 2 0 01-2 2H7a3 3 0 01-3-3V6a3 3 0 013-3z" />
                                </svg>
                                <span class="font-medium">کاتیگۆریەکان</span>
                            </a>

                            <a href="{{ url('/admin/transactions') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200 group">
                                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="font-medium">مامەڵەکان</span>
                            </a>
                        </div>

                        <div class="pt-4 mt-4 border-t border-gray-200">
                            <div class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">ڕێکخستنەکان</div>

                            <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200 group">
                                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="font-medium">ڕێکخستنەکانی پڕۆفایل</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div :class="{ 'mr-64': sidebarOpen, 'mr-0': !sidebarOpen }" class="flex-1 transition-all duration-300">
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
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div class="sm:hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-3">
            <div class="flex justify-between items-center">
                <button @click="open = !open" class="p-2 rounded-lg hover:bg-white/10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <a href="{{ route('dashboard') }}" class="flex items-center">
                    <img src="{{ asset('logo/logo.PNG') }}" alt="Safin Oil" class="h-8 w-auto ml-2">
                    <span class="font-bold">Safin Oil</span>
                </a>
                <div class="w-10"></div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="open" x-transition:enter="transition-transform duration-300" x-transition:enter-start="-translate-y-full" x-transition:enter-end="translate-y-0" x-transition:leave="transition-transform duration-300" x-transition:leave-start="translate-y-0" x-transition:leave-end="-translate-y-full" class="fixed inset-0 z-50 bg-white" style="display: none;">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-3">
                <div class="flex justify-between items-center">
                    <button @click="open = false" class="p-2 rounded-lg hover:bg-white/10">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <span class="font-bold">مێنو</span>
                    <div class="w-10"></div>
                </div>
            </div>

            <div class="p-4">
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <div class="flex items-center space-x-3 rtl:space-x-reverse">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-xl">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="font-bold text-gray-800">{{ Auth::user()->name }}</div>
                            <div class="text-sm text-gray-500">{{ Auth::user()->email }}</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-600' : '' }}">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span class="font-medium">داشبۆرد</span>
                    </a>

                    <a href="{{ url('/admin/quick-sales') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200 {{ request()->is('admin/quick-sales*') ? 'bg-indigo-50 text-indigo-600' : '' }}">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span class="font-medium">فرۆشی خێرا</span>
                    </a>

                    <a href="{{ url('/admin/categories') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l5 5a2 2 0 01.586 1.414V19a2 2 0 01-2 2H7a3 3 0 01-3-3V6a3 3 0 013-3z" />
                        </svg>
                        <span class="font-medium">کاتیگۆریەکان</span>
                    </a>

                    <a href="{{ url('/admin/transactions') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200">
                        <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">مامەڵەکان</span>
                    </a>

                    <div class="pt-4 mt-4 border-t border-gray-200">
                        <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200">
                            <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="font-medium">پڕۆفایل</span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="mt-2">
                            @csrf
                            <button type="submit" class="w-full text-right flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                                <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span class="font-medium">چوونە دەرەوە</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Content -->
        <div>
            @isset($header)
                <header class="bg-white shadow">
                    <div class="px-4 py-6">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot }}
            </main>
        </div>
    </div>
</nav>
