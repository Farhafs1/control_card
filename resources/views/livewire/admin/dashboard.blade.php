<div class="space-y-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        
        <div class="lg:col-span-2 bg-white p-8 rounded-[2rem] border border-emerald-100 shadow-sm relative overflow-hidden flex flex-col justify-center">
            <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-50 rounded-full -mr-16 -mt-16 opacity-40"></div>
            <div class="relative z-10">
                <p class="text-[10px] font-black text-emerald-600 uppercase tracking-[0.2em]">Total Inflow (Revenue)</p>
                <h3 class="text-4xl font-black text-slate-900 mt-2 leading-none">
                    <span class="text-emerald-800 italic serif">₦</span> {{ number_format($totalRevenue, 2) }}
                </h3>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white p-8 rounded-[2rem] border border-rose-100 shadow-sm relative overflow-hidden flex flex-col justify-center">
            <div class="absolute top-0 right-0 w-32 h-32 bg-rose-50 rounded-full -mr-16 -mt-16 opacity-40"></div>
            <div class="relative z-10">
                <p class="text-[10px] font-black text-rose-600 uppercase tracking-[0.2em]">Total Outflow (Expenditure)</p>
                <h3 class="text-4xl font-black text-slate-900 mt-2 leading-none">
                    <span class="text-rose-800 italic serif">₦</span> {{ number_format($totalExpenditure, 2) }}
                </h3>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <div class="lg:col-span-7 bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm">
            <div class="flex justify-between items-center mb-10">
                <h2 class="serif text-2xl text-slate-900">Budget Classification</h2>
                <span class="text-[9px] font-black bg-slate-50 px-3 py-1 rounded-full uppercase tracking-widest text-slate-400 border border-slate-100">Live Allocation</span>
            </div>

            <div class="space-y-8">
                @php
                    $order = [
                        ['label' => 'Revenue', 'key' => 'revenue', 'color' => 'bg-blue-600'],
                        ['label' => 'Personnel Cost', 'key' => 'personnel', 'color' => 'bg-emerald-700'],
                        ['label' => 'Overhead Cost', 'key' => 'overhead', 'color' => 'bg-amber-500'],
                        ['label' => 'Capital Expenditure', 'key' => 'capital', 'color' => 'bg-rose-700'],
                    ];
                @endphp

                @foreach($order as $item)
                <div class="group">
                    <div class="flex justify-between items-end mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-1.5 h-4 {{ $item['color'] }} rounded-full opacity-80 group-hover:opacity-100 transition-opacity"></div>
                            <span class="text-[11px] font-black text-slate-800 uppercase tracking-widest">{{ $item['label'] }}</span>
                        </div>
                        <span class="text-xs font-bold text-slate-500 font-mono">₦ {{ number_format($breakdown[$item['key']], 2) }}</span>
                    </div>
                    <div class="w-full bg-slate-50 rounded-full h-2 overflow-hidden border border-slate-100/50">
                        @php
                            $denominator = ($item['key'] === 'revenue') ? ($totalRevenue + $totalExpenditure) : ($totalRevenue + $totalExpenditure);
                            $percentage = $denominator > 0 ? ($breakdown[$item['key']] / $denominator) * 100 : 0;
                        @endphp
                        <div class="h-full {{ $item['color'] }} transition-all duration-1000 ease-out shadow-inner" 
                             style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="lg:col-span-5 flex flex-col gap-6">
            <div class="bg-slate-900 p-8 rounded-[3rem] text-white flex-1 relative overflow-hidden shadow-2xl">
                 <div class="flex items-center gap-2 mb-6">
                     <div class="w-1 h-4 bg-emerald-500 rounded-full"></div>
                     <h2 class="text-[10px] font-black text-emerald-400 uppercase tracking-[0.2em]">Live Synchronizations</h2>
                 </div>

                 <div class="space-y-5">
                    @forelse($recentActivity as $activity)
                    <div class="flex items-center justify-between border-b border-white/5 pb-4 group">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-white uppercase truncate w-40 group-hover:text-emerald-400 transition-colors">{{ $activity->description }}</span>
                            <span class="text-[8px] text-slate-500 font-bold uppercase mt-1">{{ $activity->mda->name ?? 'System' }}</span>
                        </div>
                        <span class="text-[8px] font-black text-slate-400 uppercase bg-white/5 px-2 py-1 rounded tracking-tighter">{{ $activity->created_at->diffForHumans() }}</span>
                    </div>
                    @empty
                    <p class="text-[10px] text-slate-500 uppercase italic">No recent synchronizations recorded.</p>
                    @endforelse
                 </div>
            </div>

            <a href="{{ route('admin.budget-upload') }}" class="group bg-emerald-50 border border-emerald-100 p-6 rounded-[2.5rem] flex items-center justify-between hover:bg-emerald-900 transition-all duration-500 shadow-sm hover:shadow-emerald-900/20">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-emerald-900 shadow-sm group-hover:rotate-90 transition-transform duration-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <span class="text-[11px] font-black text-emerald-900 group-hover:text-white uppercase tracking-widest transition-colors">Manage System Data</span>
                </div>
                <svg class="w-4 h-4 text-emerald-900 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke-width="3"/></svg>
            </a>
        </div>
    </div>
</div>