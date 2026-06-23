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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght=700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    @livewireStyles
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
        
        <div x-show="mobileMenu" @click="mobileMenu = false" x-cloak class="fixed inset-0 z-20 bg-black/50 lg:hidden"></div>

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
                    <div :class="isMinimized ? 'w-10 h-10' : 'w-16 h-16'" class="w-16 h-16 bg-white rounded-full p-1 shadow-inner border-2 border-emerald-600 overflow-hidden transition-all duration-300">
                        <div class="w-full h-full bg-emerald-50 rounded-full flex items-center justify-center overflow-hidden">
                            @if($siteSettings && $siteSettings->logo_path)
                                <img src="{{ Storage::url($siteSettings->logo_path) }}" class="w-full h-full object-cover">
                            @else
                                <img src="{{ asset('assets/images/katsina-crest.png') }}" class="w-full h-full object-cover">
                            @endif
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

            <nav class="flex-1 overflow-y-auto py-6 space-y-2 custom-scrollbar overflow-x-hidden">
                
                {{-- ADMIN SECTION --}}
                @if(auth()->user()->role === 'admin')
                    <nav class="flex-1 overflow-y-auto py-6 space-y-2 custom-scrollbar overflow-x-hidden" 
                        x-data="{ 
                            activeCategory: null,
                            toggleCategory(category) {
                                if (this.activeCategory === category) {
                                    this.activeCategory = null;
                                } else {
                                    this.activeCategory = category;
                                }
                            }
                        }">
                        
                        {{-- ===== STRATEGIC INSIGHTS DROPDOWN ===== --}}
                        <div class="px-3">
                            <button @click="toggleCategory('insights')" 
                                    class="w-full flex items-center justify-between px-3 py-2 text-[10px] font-bold text-emerald-500/60 uppercase tracking-[0.2em] hover:text-white transition-colors group">
                                <span x-show="!isMinimized">{{ __('Strategic Insights') }}</span>
                                <i x-show="!isMinimized" class="fas fa-chevron-down text-[8px] transition-transform duration-300" :class="activeCategory === 'insights' ? 'rotate-180' : ''"></i>
                                <i x-show="isMinimized" class="fas fa-chart-pie w-full text-center text-emerald-500"></i>
                            </button>

                            <div x-show="activeCategory === 'insights' || isMinimized" 
                                x-collapse.duration.300ms
                                x-cloak 
                                class="mt-1 space-y-1">
                                <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                                    <i class="fas fa-th-large w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Central Dashboard</span>
                                </x-nav-link>

                                <x-nav-link href="{{ route('admin.analytics.budget') }}" :active="request()->routeIs('admin.analytics.budget')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                                    <i class="fas fa-file-invoice-dollar w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('admin.analytics.budget') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Budget Analytics</span>
                                </x-nav-link>

                                <x-nav-link href="{{ route('admin.analytics.performance') }}" :active="request()->routeIs('admin.analytics.performance')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                                    <i class="fas fa-balance-scale w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('admin.analytics.performance') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Performance Ranking</span>
                                </x-nav-link>

                                <x-nav-link href="{{ route('admin.budget-performance') }}" :active="request()->routeIs('admin.budget-performance')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                                    <i class="fas fa-chart-line w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('admin.budget-performance') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Budget Performance</span>
                                </x-nav-link>

                                <x-nav-link href="{{ route('admin.comparative-analysis') }}" :active="request()->routeIs('admin.comparative-analysis')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                                    <i class="fas fa-balance-scale w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('admin.comparative-analysis') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Comparative Analysis</span>
                                </x-nav-link>

                                <x-nav-link href="{{ route('admin.analytics.expenditure') }}" :active="request()->routeIs('admin.analytics.expenditure')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                                    <i class="fas fa-project-diagram w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('admin.analytics.expenditure') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Release Trends</span>
                                </x-nav-link>
                            </div>
                        </div>

                        {{-- ===== BUDGETARY OPERATIONS DROPDOWN ===== --}}
                        <div class="px-3 mt-4">
                            <button @click="toggleCategory('operations')" 
                                    class="w-full flex items-center justify-between px-3 py-2 text-[10px] font-bold text-emerald-500/60 uppercase tracking-[0.2em] hover:text-white transition-colors">
                                <span x-show="!isMinimized">{{ __('Budgetary Operations') }}</span>
                                <i x-show="!isMinimized" class="fas fa-chevron-down text-[8px] transition-transform duration-300" :class="activeCategory === 'operations' ? 'rotate-180' : ''"></i>
                                <i x-show="isMinimized" class="fas fa-cogs w-full text-center text-emerald-500"></i>
                            </button>

                            <div x-show="activeCategory === 'operations' || isMinimized" 
                                x-collapse.duration.300ms
                                x-cloak 
                                class="mt-1 space-y-1">
                                <x-nav-link href="{{ route('admin.data-extraction') }}" :active="request()->routeIs('admin.data-extraction')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                                    <i class="fas fa-robot w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('admin.data-extraction') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Data Extractor</span>
                                </x-nav-link>

                                <x-nav-link href="{{ route('admin.budget-upload') }}" :active="request()->routeIs('admin.budget-upload')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                                    <i class="fas fa-cloud-upload-alt w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('admin.budget-upload') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Annual Provisioning</span>
                                </x-nav-link>

                                <x-nav-link href="{{ route('admin.subheads') }}" :active="request()->routeIs('admin.subheads*')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                                    <i class="fas fa-book w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('admin.subheads*') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Subhead Ledgers</span>
                                </x-nav-link>

                                <x-nav-link href="{{ route('admin.expenditure') }}" :active="request()->routeIs('admin.expenditure')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                                    <i class="fas fa-route w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('admin.expenditure') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Batch Tracking</span>
                                </x-nav-link>
                            </div>
                        </div>

                        {{-- ===== GOVERNANCE DROPDOWN ===== --}}
                        <div class="px-3 mt-4">
                            <button @click="toggleCategory('governance')" 
                                    class="w-full flex items-center justify-between px-3 py-2 text-[10px] font-bold text-emerald-500/60 uppercase tracking-[0.2em] hover:text-white transition-colors">
                                <span x-show="!isMinimized">{{ __('Governance') }}</span>
                                <i x-show="!isMinimized" class="fas fa-chevron-down text-[8px] transition-transform duration-300" :class="activeCategory === 'governance' ? 'rotate-180' : ''"></i>
                                <i x-show="isMinimized" class="fas fa-shield-alt w-full text-center text-emerald-500"></i>
                            </button>

                            <div x-show="activeCategory === 'governance' || isMinimized" 
                                x-collapse.duration.300ms
                                x-cloak 
                                class="mt-1 space-y-1">
                                <x-nav-link href="{{ route('admin.mdas') }}" :active="request()->routeIs('admin.mdas')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                                    <i class="fas fa-university w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('admin.mdas') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">MDA Directory</span>
                                </x-nav-link>

                                <x-nav-link href="{{ route('admin.users') }}" :active="request()->routeIs('admin.users')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                                    <i class="fas fa-user-shield w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('admin.users') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Access Control</span>
                                </x-nav-link>

                                <x-nav-link href="{{ route('admin.system-logs') }}" :active="request()->routeIs('admin.system-logs')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                                    <i class="fas fa-fingerprint w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('admin.system-logs') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Audit Logs</span>
                                </x-nav-link>

                                <x-nav-link href="{{ route('admin.settings') }}" :active="request()->routeIs('admin.settings')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                                    <i class="fas fa-sliders-h w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('admin.settings') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                                    <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">System Config</span>
                                </x-nav-link>
                            </div>
                        </div>
                    </nav>
                @endif

                {{-- ANALYST SECTION --}}
                @if(auth()->user()->role === 'analyst')
                    {{-- Strategic Insights --}}
                    <div class="px-6 mb-2 text-[10px] font-bold text-emerald-500/60 uppercase tracking-[0.2em]">Strategic Insights</div>
                    
                    <x-nav-link href="{{ route('analyst.dashboard') }}" :active="request()->routeIs('analyst.dashboard')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                        <i class="fas fa-th-large w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('analyst.dashboard') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                        <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Central Dashboard</span>
                    </x-nav-link>

                    <x-nav-link href="{{ route('analyst.analytics.budget') }}" :active="request()->routeIs('analyst.analytics.budget')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                        <i class="fas fa-file-invoice-dollar w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('analyst.analytics.budget') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                        <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Budget Analytics</span>
                    </x-nav-link>

                    <x-nav-link href="{{ route('analyst.analytics.performance') }}" :active="request()->routeIs('analyst.analytics.performance')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                        <i class="fas fa-balance-scale w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('analyst.analytics.performance') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                        <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Performance Ranking</span>
                    </x-nav-link>

                    <x-nav-link href="{{ route('analyst.budget-performance') }}" :active="request()->routeIs('analyst.budget-performance')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                        <i class="fas fa-chart-line w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('analyst.budget-performance') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                        <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Budget Performance</span>
                    </x-nav-link>

                    <x-nav-link href="{{ route('analyst.comparative-analysis') }}" :active="request()->routeIs('analyst.comparative-analysis')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                        <i class="fas fa-balance-scale w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('analyst.comparative-analysis') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                        <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Comparative Analysis</span>
                    </x-nav-link>

                    <x-nav-link href="{{ route('analyst.analytics.expenditure') }}" :active="request()->routeIs('analyst.analytics.expenditure')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                        <i class="fas fa-project-diagram w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('analyst.analytics.expenditure') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                        <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Release Trends</span>
                    </x-nav-link>

                    <div class="my-4 border-t border-emerald-800/30 mx-6"></div>

                    {{-- Account --}}
                    <div class="px-6 mb-2 text-[10px] font-bold text-emerald-500/60 uppercase tracking-[0.2em]">Account</div>

                    {{-- Use the analyst.profile route --}}
                    <x-nav-link href="{{ route('analyst.profile') }}" :active="request()->routeIs('analyst.profile')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                        <i class="fas fa-user-cog w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('analyst.profile') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                        <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Profile Settings</span>
                    </x-nav-link>
                @endif

                {{-- OFFICER SECTION --}}
                @if(auth()->user()->role === 'officer')
                    <div class="px-6 mb-2 text-[10px] font-bold text-emerald-500/60 uppercase tracking-[0.2em]">Operations</div>
                    
                    <x-nav-link href="{{ route('officer.dashboard') }}" :active="request()->routeIs('officer.dashboard')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                        <i class="fas fa-tachometer-alt w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('officer.dashboard') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                        <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Dashboard</span>
                    </x-nav-link>

                    <x-nav-link href="{{ route('officer.mda-explorer') }}" :active="request()->routeIs('officer.mda-explorer')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                        <i class="fas fa-search-location w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('officer.mda-explorer') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                        <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">MDA Explorer</span>
                    </x-nav-link>

                    <x-nav-link href="{{ route('officer.subheads') }}" :active="request()->routeIs('officer.subheads*')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                        <i class="fas fa-file-invoice-dollar w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('officer.subheads*') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                        <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Subhead Ledgers</span>
                    </x-nav-link>

                    <x-nav-link href="{{ route('officer.recent-releases') }}" :active="request()->routeIs('officer.recent-releases')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                        <i class="fas fa-history w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('officer.recent-releases') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                        <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Recent Releases</span>
                    </x-nav-link>

                    <x-nav-link href="{{ route('officer.budget-performance') }}" :active="request()->routeIs('officer.budget-performance')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                        <i class="fas fa-chart-line w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('officer.budget-performance') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                        <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Budget Performance</span>
                    </x-nav-link>

                    <div class="my-4 border-t border-emerald-800/30 mx-6"></div>

                    {{-- Account --}}
                    <div class="px-6 mb-2 text-[10px] font-bold text-emerald-500/60 uppercase tracking-[0.2em]">Account</div>

                    <x-nav-link href="{{ route('officer.profile') }}" :active="request()->routeIs('officer.profile')" wire:navigate class="flex items-center px-6 py-2.5 rounded-lg transition-colors duration-200 hover:bg-emerald-700/50 group">
                        <i class="fas fa-user-cog w-6 text-center text-emerald-400 transition-colors duration-200 {{ request()->routeIs('officer.profile') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                        <span x-show="!isMinimized" class="ml-3 text-sm font-medium transition-opacity duration-300">Profile Settings</span>
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
                            {{ auth()->user()->role === 'admin' ? 'System Admin' : (auth()->user()->role === 'analyst' ? 'Analyst' : 'Budget Officer') }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('logout.manual') }}" class="w-full flex items-center justify-center py-2 text-[11px] font-black uppercase tracking-widest bg-rose-500/10 hover:bg-rose-500/20 text-rose-200 rounded-xl transition border border-rose-500/20 group">
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

    @livewireScripts

    <style>
        .serif { font-family: 'Playfair Display', serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.2); border-radius: 10px; }
    </style>
</body>
</html>