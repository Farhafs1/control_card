<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Control System | Katsina State</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-100 font-sans text-slate-900">
    <div class="flex h-screen overflow-hidden">
        
        <aside class="w-64 bg-[#064e3b] text-white flex-shrink-0 flex flex-col shadow-2xl z-10">
            <div class="p-6 border-b border-emerald-800/50">
                <div class="flex flex-col items-center text-center space-y-3">
                    <div class="w-16 h-16 bg-white rounded-full p-1 shadow-inner border-2 border-emerald-600">
                        <div class="w-full h-full bg-emerald-50 rounded-full flex items-center justify-center">
                            <span class="text-emerald-900 font-black text-xl tracking-tighter">KTS</span>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xs font-bold text-emerald-400 uppercase tracking-widest leading-tight">Ministry of Budget &<br>Economic Planning</h2>
                        <p class="text-[10px] text-emerald-100/50 mt-1 uppercase tracking-tighter font-semibold">Katsina State Govt.</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-1 custom-scrollbar">
                <div class="px-3 mb-2 text-[10px] font-bold text-emerald-500/60 uppercase tracking-[0.2em]">General</div>
                <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">Dashboard</x-nav-link>
                <x-nav-link href="{{ route('admin.users') }}" :active="request()->routeIs('admin.users')">BO Management</x-nav-link>
                <x-nav-link href="{{ route('admin.mdas') }}" :active="request()->routeIs('admin.mdas')">MDA Management</x-nav-link>

                <div class="px-3 mt-8 mb-2 text-[10px] font-bold text-emerald-500/60 uppercase tracking-[0.2em]">Budget Hub</div>
                <x-nav-link href="{{ route('admin.budget-upload') }}" :active="request()->routeIs('admin.budget-upload')">Upload Budget</x-nav-link>
                <x-nav-link href="{{ route('admin.expenditure') }}" :active="request()->routeIs('admin.expenditure')">Expenditure Tracking</x-nav-link>
                <x-nav-link href="{{ route('admin.subheads') }}" :active="request()->routeIs('admin.subheads')">Subheads</x-nav-link>

                <div class="px-3 mt-8 mb-2 text-[10px] font-bold text-emerald-500/60 uppercase tracking-[0.2em]">Settings</div>
                <x-nav-link href="#">System Logs</x-nav-link>
                <x-nav-link href="#">Profile</x-nav-link>
            </nav>

            <div class="p-4 bg-emerald-950/40 border-t border-emerald-800/40">
                <div class="flex items-center space-x-3 mb-3 px-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center text-xs font-bold text-white shadow-sm border border-emerald-500/50">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-emerald-400 font-medium uppercase tracking-tighter">System Admin</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center space-x-2 py-2 text-[11px] font-black uppercase tracking-widest bg-white/5 hover:bg-rose-500/20 text-emerald-100 hover:text-rose-200 rounded-xl transition duration-300 border border-white/10 group">
                        <svg class="w-3 h-3 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-10 shadow-sm z-0">
                <div>
                    <h1 class="text-xl font-black text-slate-800 tracking-tight uppercase">Budget Control System</h1>
                    <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Katsina State Goverment</p>
                </div>
                
                <div class="flex items-center space-x-6">
                    <div class="text-right hidden md:block">
                        <p class="text-xs font-bold text-slate-500">{{ now()->format('l, jS F Y') }}</p>
                        <p class="text-[10px] text-emerald-600 font-bold uppercase tracking-tighter">Fiscal Year: {{ now()->year }}</p>
                    </div>
                    <div class="h-8 w-[1px] bg-slate-200"></div>
                    
                    <button class="relative p-2 text-slate-400 hover:text-emerald-600 transition group">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-rose-500 rounded-full border-2 border-white group-hover:animate-ping"></span>
                    </button>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-10 bg-slate-100">
                <div class="max-w-7xl mx-auto">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>

    <style>
        /* Modern Font Injection */
        .serif { font-family: 'Playfair Display', serif; }
        
        /* Custom Scrollbar Logic */
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.2); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(16, 185, 129, 0.4); }

        /* Navigation Link Component Styling (If not in a separate file) */
        nav x-nav-link { transition: all 0.3s ease; }
    </style>
</body>
</html>