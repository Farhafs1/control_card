<div class="space-y-6">
    {{-- Welcome Header --}}
    <div class="flex justify-between items-end">
        <div>
            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Welcome, {{ auth()->user()->name }}</h2>
            <p class="text-sm text-slate-500 font-medium">
                Portfolio: <span class="text-emerald-600 font-bold">{{ $mdas->count() }} Assigned MDAs</span>
            </p>
        </div>
        <div class="flex space-x-3">
            {{-- NEW: Button to jump to general Explorer --}}
            <a href="{{ route('officer.mda-explorer') }}" wire:navigate class="bg-emerald-100 hover:bg-emerald-200 text-emerald-800 px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center">
                <i class="fas fa-search-dollar mr-2"></i> Explorer
            </a>

            <a href="{{ route('admin.expenditure.upload') }}" wire:navigate class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-lg shadow-blue-200 flex items-center">
                <i class="fas fa-file-import mr-2"></i> Post Expenditure
            </a>
        </div>
    </div>

    @if($mdas->isEmpty())
        {{-- Empty State (Keep as is) --}}
        <div class="bg-amber-50 border-2 border-amber-100 p-6 rounded-2xl flex items-center space-x-4">
            <div class="bg-amber-100 p-3 rounded-full text-amber-600">
                <i class="fas fa-user-shield text-xl"></i>
            </div>
            <div>
                <p class="text-amber-800 font-bold uppercase text-xs tracking-widest">Access Restricted</p>
                <p class="text-amber-700 text-sm font-medium leading-relaxed">You have not been assigned to any MDA yet.</p>
            </div>
        </div>
    @else
        {{-- Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Portfolio Allocation</p>
                <h3 class="text-2xl font-black text-slate-800">₦{{ number_format($stats['total_allocation'], 2) }}</h3>
            </div>
            
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Releases</p>
                <h3 class="text-2xl font-black text-emerald-600">₦{{ number_format($stats['total_spent'], 2) }}</h3>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Current Balance</p>
                <h3 class="text-2xl font-black text-blue-600">₦{{ number_format($stats['total_allocation'] - $stats['total_spent'], 2) }}</h3>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- MDA Summary Table --}}
            <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-xl shadow-slate-200/50 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                    <h4 class="text-xs font-black text-slate-700 uppercase tracking-widest">My Assigned MDAs</h4>
                    <span class="text-[10px] font-bold text-slate-400">Click to explore breakdown</span>
                </div>
                
                <div class="divide-y divide-slate-50">
                    @foreach($mdas as $mda)
                        @php
                            $mdaSpent = $mda->releases->where('is_cancelled', false)->sum('amount');
                            $mdaTotal = $mda->subheads->sum(fn($s) => $s->approved_provision + $s->additional_provision);
                            $percent = $mdaTotal > 0 ? ($mdaSpent / $mdaTotal) * 100 : 0;
                            $color = $percent > 90 ? 'bg-rose-500' : ($percent > 70 ? 'bg-amber-500' : 'bg-emerald-500');
                        @endphp
                        {{-- UPDATED: Entire row is now a link to the explorer --}}
                        <a href="{{ route('officer.mda-explorer', ['selectedMdaId' => $mda->id]) }}" wire:navigate class="block p-5 hover:bg-slate-50 transition-colors group">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-[10px] font-black text-blue-600 bg-blue-50 px-2 py-0.5 rounded group-hover:bg-blue-600 group-hover:text-white transition-colors">{{ $mda->mda_code }}</span>
                                        <h5 class="text-sm font-bold text-slate-800 group-hover:text-emerald-700">{{ $mda->name }}</h5>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1">₦{{ number_format($mdaSpent, 2) }} spent of ₦{{ number_format($mdaTotal, 2) }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-black {{ $percent > 90 ? 'text-rose-600' : 'text-slate-700' }}">{{ number_format($percent, 1) }}%</span>
                                </div>
                            </div>
                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $color }} rounded-full transition-all duration-700" style="width: {{ $percent }}%"></div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Critical Alerts (Keep as is) --}}
            <div class="space-y-6">
                <div class="bg-rose-50 p-6 rounded-3xl border border-rose-100 shadow-sm">
                    <h3 class="text-xs font-black text-rose-700 uppercase mb-4 flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Low Balance Alerts
                    </h3>
                    <div class="space-y-3">
                        @forelse($criticalSubheads as $sub)
                            <div class="bg-white p-4 rounded-2xl border border-rose-200 shadow-sm hover:translate-x-1 transition-transform">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ $sub->subhead_code }}</p>
                                <p class="text-xs font-black text-slate-800 truncate">{{ $sub->description }}</p>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-[10px] font-bold text-rose-600">Critical</span>
                                    {{-- Use officer explorer for details if admin route is restricted --}}
                                    <a href="{{ route('officer.mda-explorer', ['selectedMdaId' => $sub->mda_id]) }}" wire:navigate class="text-[10px] font-black text-blue-600 hover:underline uppercase">Analyze MDA</a>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <p class="text-xs text-slate-500 font-medium">All subheads within limits.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-slate-900 p-6 rounded-3xl shadow-xl">
                    <p class="text-white text-xs font-black uppercase tracking-widest mb-4">Pending Tasks</p>
                    <div class="flex items-center justify-between text-slate-400">
                        <span class="text-xs font-medium italic">Awaiting verification...</span>
                        <span class="bg-slate-800 text-white px-2 py-1 rounded-lg text-xs font-black">{{ $stats['pending_count'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>