<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $siteSettings->app_name ?? 'Budget Control System' }} | {{ $siteSettings->state_name ?? 'Katsina State' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-100 font-sans text-slate-900" 
      x-data="{ 
        mobileMenu: false, 
        isMinimized: false,
        toggle() {
            if (window.innerWidth >= 1024) {
                this.isMinimized = !this.isMinimized;
            } else {
                this.mobileMenu = !this.mobileMenu;
            }
        }
      }">

    <div class="flex h-screen overflow-hidden">
        
        <div x-show="mobileMenu" @click="mobileMenu = false" class="fixed inset-0 z-20 bg-black/50 lg:hidden"></div>

        <aside 
            :class="{
                'translate-x-0': mobileMenu, 
                '-translate-x-full': !mobileMenu,
                'w-64': !isMinimized,
                'w-20': isMinimized
            }"
            class="fixed inset-y-0 left-0 z-30 bg-[#064e3b] text-white flex-shrink-0 flex flex-col shadow-2xl transition-all duration-300 lg:relative lg:translate-x-0">
            
            <div class="p-6 border-b border-emerald-800/50">
                <div class="flex flex-col items-center text-center">
                    <div :class="isMinimized ? 'w-10 h-10' : 'w-16 h-16'" class="bg-white rounded-full p-1 shadow-inner border-2 border-emerald-600 overflow-hidden transition-all duration-300">
                        <div class="w-full h-full bg-emerald-50 rounded-full flex items-center justify-center overflow-hidden">
                            <div class="w-full h-full bg-emerald-50 rounded-full flex items-center justify-center overflow-hidden">
                                @if($siteSettings && $siteSettings->logo_path)
                                    <img src="{{ Storage::url($siteSettings->logo_path) }}" class="w-full h-full object-cover">
                                @else
                                    {{-- Fallback to the local asset if no database logo is set --}}
                                    <img src="{{ asset('assets/images/katsina-crest.png') }}" class="w-full h-full object-cover">
                                @endif
                            </div>
                        </div>
                    </div>
                    <div x-show="!isMinimized" class="mt-3 transition-opacity duration-300">
                        <h2 class="text-xs font-bold text-emerald-400 uppercase tracking-widest leading-tight">
                            {{ $siteSettings->app_name ?? 'Ministry of Budget & Planning' }}
                        </h2>
                        <p class="text-[10px] text-emerald-100/50 mt-1 uppercase tracking-tighter font-semibold">
                            {{ $siteSettings->state_name ?? 'Katsina State Govt.' }}
                        </p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto py-6 space-y-2 custom-scrollbar overflow-x-hidden" 
                x-data="{ 
                    openGroup: '{{ request()->is('admin/analytics*') || request()->routeIs('analytics.*') ? 'insights' : (request()->is('admin/budget*') || request()->is('admin/subheads*') ? 'ops' : '') }}' 
                }">

                @if(auth()->user()->role === 'admin')
                    <div class="px-3">
                        <button @click="openGroup = (openGroup === 'insights' ? '' : 'insights')" 
                                class="w-full flex items-center justify-between px-3 py-2 text-[10px] font-bold text-emerald-500/60 uppercase tracking-[0.2em] hover:text-white transition-colors group">
                            <span x-show="!isMinimized">{{ __('Strategic Insights') }}</span>
                            <i x-show="!isMinimized" class="fas fa-chevron-down text-[8px] transition-transform duration-300" :class="openGroup === 'insights' ? 'rotate-180' : ''"></i>
                            <i x-show="isMinimized" class="fas fa-chart-pie w-full text-center text-emerald-500"></i>
                        </button>

                        <div x-show="openGroup === 'insights' || isMinimized" x-collapse x-cloak class="mt-1 space-y-1">
                            <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')" wire:navigate class="flex items-center px-6 py-2 rounded-lg">
                                <i class="fas fa-th-large w-5"></i> 
                                <span x-show="!isMinimized" class="ml-3 text-sm">Central Dashboard</span>
                            </x-nav-link>

                            <x-nav-link href="{{ route('admin.analytics.budget') }}" 
                                        :active="request()->routeIs('admin.analytics.budget')" 
                                        wire:navigate 
                                        class="flex items-center px-6 py-2 rounded-lg">
                                <i class="fas fa-file-invoice-dollar w-5"></i> 
                                <span x-show="!isMinimized" class="ml-3 text-sm">Budget Analytics</span>
                            </x-nav-link>

                            <x-nav-link href="{{ route('admin.analytics.performance') }}" 
                                        :active="request()->routeIs('admin.analytics.performance')"
                                        wire:navigate 
                                        class="flex items-center px-6 py-2 rounded-lg">
                                <i class="fas fa-balance-scale w-5"></i> 
                                <span x-show="!isMinimized" class="ml-3 text-sm">Performance Ranking</span>
                            </x-nav-link>

                            <!-- Budget Performance Link -->
                            <x-nav-link href="{{ route('admin.budget-performance') }}" 
                                        :active="request()->routeIs('admin.budget-performance')" 
                                        wire:navigate 
                                        class="flex items-center px-6 py-2 rounded-lg transition-colors duration-200 hover:bg-gray-700 group">
                                <div class="flex items-center w-full">
                                    <i class="fas fa-chart-line w-5 text-center transition-colors duration-200 {{ request()->routeIs('admin.budget-performance') ? 'text-blue-400' : 'text-gray-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" 
                                        class="ml-3 text-sm font-medium transition-opacity duration-300"
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-0"
                                        x-transition:enter-end="opacity-100">
                                        Budget Performance
                                    </span>
                                </div>
                            </x-nav-link>

                            <!-- Comparative Analysis Link -->
                            <x-nav-link href="{{ route('admin.comparative-analysis') }}" 
                                        :active="request()->routeIs('admin.comparative-analysis')" 
                                        wire:navigate 
                                        class="flex items-center px-6 py-2 rounded-lg transition-colors duration-200 hover:bg-gray-700 group">
                                <div class="flex items-center w-full">
                                    <i class="fas fa-balance-scale w-5 text-center transition-colors duration-200 {{ request()->routeIs('admin.comparative-analysis') ? 'text-blue-400' : 'text-gray-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" 
                                        class="ml-3 text-sm font-medium transition-opacity duration-300"
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-0"
                                        x-transition:enter-end="opacity-100">
                                        Comparative Analysis
                                    </span>
                                </div>
                            </x-nav-link>

                            <x-nav-link href="{{ route('admin.analytics.expenditure') }}" :active="request()->routeIs('admin.analytics.expenditure')" wire:navigate class="flex items-center px-6 py-2 rounded-lg">
                                <i class="fas fa-project-diagram w-5"></i> 
                                <span x-show="!isMinimized" class="ml-3 text-sm">Release Trends</span>
                            </x-nav-link>
                        </div>
                    </div>

                    <div class="px-3 mt-4">
                        <button @click="openGroup = (openGroup === 'ops' ? '' : 'ops')" 
                                class="w-full flex items-center justify-between px-3 py-2 text-[10px] font-bold text-emerald-500/60 uppercase tracking-[0.2em] hover:text-white transition-colors">
                            <span x-show="!isMinimized">Budgetary Operations</span>
                            <i x-show="!isMinimized" class="fas fa-chevron-down text-[8px] transition-transform duration-300" :class="openGroup === 'ops' ? 'rotate-180' : ''"></i>
                            <i x-show="isMinimized" class="fas fa-cogs w-full text-center text-emerald-500"></i>
                        </button>

                        <div x-show="openGroup === 'ops' || isMinimized" x-collapse x-cloak class="mt-1 space-y-1">
                            <x-nav-link href="{{ route('admin.data-extraction') }}" :active="request()->routeIs('admin.data-extraction')" wire:navigate class="flex items-center px-6 py-2 rounded-lg">
                                <i class="fas fa-robot w-5 text-emerald-400"></i> <span x-show="!isMinimized" class="ml-3 text-sm">AI Data Extraction</span>
                            </x-nav-link>
                            
                            <x-nav-link href="{{ route('admin.budget-upload') }}" :active="request()->routeIs('admin.budget-upload')" wire:navigate class="flex items-center px-6 py-2 rounded-lg">
                                <i class="fas fa-cloud-upload-alt w-5"></i> <span x-show="!isMinimized" class="ml-3 text-sm">Annual Provisioning</span>
                            </x-nav-link>

                            <x-nav-link href="{{ route('admin.subheads') }}" :active="request()->routeIs('admin.subheads*')" wire:navigate class="flex items-center px-6 py-2 rounded-lg">
                                <i class="fas fa-book w-5"></i> <span x-show="!isMinimized" class="ml-3 text-sm">Subhead Ledgers</span>
                            </x-nav-link>

                            <x-nav-link href="{{ route('admin.expenditure') }}" :active="request()->routeIs('admin.expenditure')" wire:navigate class="flex items-center px-6 py-2 rounded-lg">
                                <i class="fas fa-route w-5"></i> <span x-show="!isMinimized" class="ml-3 text-sm">Batch Tracking</span>
                            </x-nav-link>
                        </div>
                    </div>

                    <div class="px-3 mt-4">
                        <button @click="openGroup = (openGroup === 'admin' ? '' : 'admin')" 
                                class="w-full flex items-center justify-between px-3 py-2 text-[10px] font-bold text-emerald-500/60 uppercase tracking-[0.2em] hover:text-white transition-colors">
                            <span x-show="!isMinimized">Governance</span>
                            <i x-show="!isMinimized" class="fas fa-chevron-down text-[8px] transition-transform duration-300" :class="openGroup === 'admin' ? 'rotate-180' : ''"></i>
                            <i x-show="isMinimized" class="fas fa-shield-alt w-full text-center text-emerald-500"></i>
                        </button>

                        <div x-show="openGroup === 'admin' || isMinimized" x-collapse x-cloak class="mt-1 space-y-1">
                            <x-nav-link href="{{ route('admin.mdas') }}" :active="request()->routeIs('admin.mdas')" wire:navigate class="flex items-center px-6 py-2 rounded-lg">
                                <i class="fas fa-university w-5"></i> <span x-show="!isMinimized" class="ml-3 text-sm">MDA Directory</span>
                            </x-nav-link>

                            <x-nav-link href="{{ route('admin.users') }}" :active="request()->routeIs('admin.users')" wire:navigate class="flex items-center px-6 py-2 rounded-lg">
                                <i class="fas fa-user-shield w-5"></i> <span x-show="!isMinimized" class="ml-3 text-sm">Access Control</span>
                            </x-nav-link>

                            <x-nav-link href="{{ route('admin.system-logs') }}" :active="request()->routeIs('admin.system-logs')" wire:navigate class="flex items-center px-6 py-2 rounded-lg">
                                <i class="fas fa-fingerprint w-5 text-emerald-500/50"></i> <span x-show="!isMinimized" class="ml-3 text-[11px]">Audit Logs</span>
                            </x-nav-link>

                            <x-nav-link href="{{ route('admin.settings') }}" :active="request()->routeIs('admin.settings')" wire:navigate class="flex items-center px-6 py-2 rounded-lg">
                                <i class="fas fa-sliders-h w-5"></i> <span x-show="!isMinimized" class="ml-3 text-sm">System Config</span>
                            </x-nav-link>
                        </div>
                    </div>

                @else
                    <!-- Operations Section for Officers -->
                    <div class="px-6 mb-2 text-[10px] font-bold text-emerald-500/60 uppercase tracking-[0.2em]">Operations</div>
                    
                    <!-- Dashboard -->
                    <x-nav-link href="{{ route('officer.dashboard') }}" :active="request()->routeIs('officer.dashboard')" wire:navigate class="flex items-center px-6">
                        <i class="fas fa-tachometer-alt w-6"></i> 
                        <span x-show="!isMinimized" class="ml-3">Dashboard</span>
                    </x-nav-link>

                    <!-- MDA Explorer -->
                    <x-nav-link href="{{ route('officer.mda-explorer') }}" :active="request()->routeIs('officer.mda-explorer')" wire:navigate class="flex items-center px-6">
                        <i class="fas fa-search-location w-6"></i> 
                        <span x-show="!isMinimized" class="ml-3">MDA Explorer</span>
                    </x-nav-link>

                    <!-- Subheads & Ledgers -->
                    <x-nav-link href="{{ route('officer.subheads') }}" :active="request()->routeIs('officer.subheads*')" wire:navigate class="flex items-center px-6">
                        <i class="fas fa-file-invoice-dollar w-6"></i> 
                        <span x-show="!isMinimized" class="ml-3">Subhead Ledgers</span>
                    </x-nav-link>

                    <!-- Recent Releases -->
                    <x-nav-link href="{{ route('officer.recent-releases') }}" :active="request()->routeIs('officer.recent-releases')" wire:navigate class="flex items-center px-6">
                        <i class="fas fa-history w-6"></i> 
                        <span x-show="!isMinimized" class="ml-3">Recent Releases</span>
                    </x-nav-link>

                    <div class="my-4 border-t border-slate-800/50"></div>

                    <!-- Account Section -->
                    <div class="px-6 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">Account</div>
                    
                    <x-nav-link href="{{ route('officer.profile') }}" :active="request()->routeIs('officer.profile')" wire:navigate class="flex items-center px-6">
                        <i class="fas fa-user-cog w-6"></i> 
                        <span x-show="!isMinimized" class="ml-3">Profile Settings</span>
                    </x-nav-link>
                @endif
            </nav>

            <div class="p-4 bg-emerald-950/40 border-t border-emerald-800/40">
                <div class="flex items-center mb-3 px-2" :class="isMinimized ? 'justify-center' : 'space-x-3'">
                    <div class="w-8 h-8 flex-shrink-0 rounded-lg bg-emerald-600 flex items-center justify-center text-xs font-bold text-white shadow-sm border border-emerald-500/50">
                        {{ auth()->user()->initials() }}
                    </div>
                    <div x-show="!isMinimized" class="flex-1 min-w-0 transition-opacity">
                        <p class="text-xs font-bold text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-emerald-400 font-medium uppercase tracking-tighter">
                            {{ auth()->user()->role === 'admin' ? 'System Admin' : 'Budget Officer' }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('logout.manual') }}" 
                   class="w-full flex items-center justify-center py-2 text-[11px] font-black uppercase tracking-widest bg-rose-500/10 hover:bg-rose-500/20 text-rose-200 rounded-xl transition border border-rose-500/20 group">
                    <i class="fas fa-power-off" :class="isMinimized ? '' : 'mr-2'"></i>
                    <span x-show="!isMinimized">Sign Out</span>
                </a>
            </div>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-10 shadow-sm z-10">
                <div class="flex items-center">
                    <button @click="toggle()" class="mr-4 p-2 rounded-lg bg-slate-100 text-slate-600 hover:bg-emerald-600 hover:text-white transition-all">
                        <i class="fas fa-bars" x-show="!isMinimized"></i>
                        <i class="fas fa-arrow-right" x-show="isMinimized"></i>
                    </button>
                    
                    <div>
                        <h1 class="text-sm lg:text-xl font-black text-slate-800 tracking-tight uppercase">
                            {{ $siteSettings->app_name ?? 'Budget Control' }}
                        </h1>
                        <p class="text-[9px] lg:text-[11px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">
                            {{ $siteSettings->state_name ?? 'Katsina State Government' }}
                        </p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-3 lg:space-x-6">
                    <div class="text-right hidden sm:block">
                        <p class="text-[10px] lg:text-xs font-bold text-slate-500">{{ now()->format('D, jS M Y') }}</p>
                        <p class="text-[9px] lg:text-[10px] text-emerald-600 font-bold uppercase tracking-tighter">
                            FY: {{ $siteSettings->fiscal_year ?? now()->year }}
                        </p>
                    </div>
                    <div class="h-8 w-[1px] bg-slate-200 hidden sm:block"></div>
                    
                    <button class="relative p-2 text-slate-400 hover:text-emerald-600 transition group">
                        <i class="fas fa-bell text-lg lg:text-xl"></i>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-rose-500 rounded-full border-2 border-white group-hover:animate-ping"></span>
                    </button>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-4 lg:p-10 bg-slate-100">
                <div class="max-w-7xl mx-auto">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>

    <style>
        .serif { font-family: 'Playfair Display', serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.2); border-radius: 10px; }
    </style>
</body>
</html>