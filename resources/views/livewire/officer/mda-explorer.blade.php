<div class="space-y-6">
    {{-- MDA Selection Bar --}}
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-emerald-100">
        <h2 class="text-xs font-black text-emerald-900 uppercase tracking-widest mb-3 px-2">Your Assigned MDAs</h2>
        <div class="flex flex-wrap gap-3">
            @foreach($mdas as $mda)
                <button wire:click="selectMda({{ $mda->id }})" 
                    class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all border-2 {{ $selectedMdaId == $mda->id ? 'bg-emerald-800 text-white border-emerald-900 shadow-lg' : 'bg-emerald-50 text-emerald-800 border-transparent hover:bg-emerald-100' }}">
                    {{ $mda->name }}
                </button>
            @endforeach
        </div>
    </div>

    @if($currentMda)
        {{-- Top Level Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-emerald-900 rounded-3xl p-8 text-white shadow-2xl relative overflow-hidden group">
                <p class="text-emerald-300 text-xs font-black uppercase tracking-[0.2em] mb-1">Total MDA Budget Provision</p>
                <h3 class="text-3xl font-black">₦{{ number_format($currentMda->subheads->sum(fn($s) => $s->approved_provision + $s->additional_provision), 2) }}</h3>
                
                {{-- FIX 1: MDA Code and Sector --}}
                <div class="mt-4 flex flex-col space-y-1">
                    <div class="inline-flex items-center px-3 py-1 bg-white/10 rounded-full text-[10px] font-bold text-emerald-100 border border-white/20 uppercase w-max">
                        Sector: {{ $currentMda->sector }}
                    </div>
                    <div class="text-[14px] font-black text-emerald-400 uppercase tracking-widest ml-1">
                        MDA Code: {{ $currentMda->mda_code }}
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl p-8 border border-emerald-100 shadow-sm flex flex-col justify-center">
                
                <p class="text-gray-400 text-xs font-black uppercase tracking-[0.2em] mb-1">Overall Expenditure Performance</p>
                @php 
                    $totalProv = $currentMda->subheads->sum(fn($s) => $s->approved_provision + $s->additional_provision);
                    $totalPerf = $currentMda->subheads->sum('releases_sum_amount');
                    $perc = $totalProv > 0 ? ($totalPerf / $totalProv) * 100 : 0;
                @endphp
                <h3 class="text-3xl font-black text-emerald-900">₦{{ number_format($totalPerf, 2) }}</h3>
                <div class="mt-6">
                    <!-- <img src="{{ asset('assets/images/katsina-crest.png') }}" class="absolute right-[-30px] bottom-[-30px] h-48 opacity-10 pointer-events-none transform group-hover:scale-110 transition-transform"> -->
                    <div class="flex justify-between text-[12px] font-black uppercase mb-2">
                        <span class="text-emerald-700">Utilization Rate</span>
                        <span class="{{ $perc > 90 ? 'text-red-600' : 'text-yellow-600' }}">{{ number_format($perc, 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 h-3 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-1000 {{ $perc > 90 ? 'bg-red-500' : 'bg-yellow-500' }}" style="width: {{ $perc }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Category Selection Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach(['Revenue', 'Personnel', 'Overhead', 'Capital'] as $cat)
                <button wire:click="setCategory('{{ $cat }}')" 
                    class="p-6 rounded-3xl text-left transition-all border-b-8 shadow-sm {{ $activeCategory == $cat ? 'bg-white border-yellow-500 ring-2 ring-emerald-50' : 'bg-gray-50 border-transparent opacity-60 hover:opacity-100' }}">
                    <p class="text-[10px] font-black uppercase tracking-widest text-emerald-800">{{ $cat }}</p>
                    <p class="text-xl font-black text-emerald-900 mt-2">₦{{ number_format($stats[$cat]['performance'] ?? 0, 0) }}</p>
                    <p class="text-[10px] text-gray-500 mt-1 uppercase font-bold">Budget: ₦{{ number_format($stats[$cat]['provision'] ?? 0, 0) }}</p>
                </button>
            @endforeach
        </div>

        {{-- Subhead Table for Active Category --}}
        <div class="bg-white rounded-3xl shadow-xl border border-emerald-50 overflow-hidden">
            <div class="px-8 py-6 border-b border-emerald-50 bg-emerald-50/30 flex justify-between items-center">
                <h4 class="font-black text-emerald-900 uppercase tracking-tighter text-lg">
                    Showing <span class="text-yellow-600">{{ $activeCategory }}</span> Details
                </h4>
                <button class="bg-emerald-800 text-white px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-emerald-900 transition-all shadow-lg">
                    <i class="fas fa-file-export mr-2"></i> Export {{ $activeCategory }}
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-sm font-bold uppercase font-black text-emerald-800 tracking-widest">
                        <tr>
                            <th class="px-8 py-5">Subhead / Line Item</th>
                            <th class="px-8 py-5">Approved + Additional</th>
                            <th class="px-8 py-5">Total Expenditure</th>
                            <th class="px-8 py-5">Balance Available</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 ">
                        @forelse($filteredSubheads as $sub)
                            @php 
                                $subTotalProv = $sub->approved_provision + $sub->additional_provision;
                                $subSpent = $sub->releases_sum_amount ?? 0; 
                                $balance = $subTotalProv - $subSpent; 
                            @endphp
                            <tr class="hover:bg-emerald-50/20 transition-colors group">
                                <td class="px-8 py-5 font-bold">
                                    <div class="font-bold text-sm text-emerald-900 group-hover:text-emerald-700 transition-colors">{{ $sub->name }}</div>
                                    <div class="font-bold text-sm text-emerald-900 font-mono tracking-tighter">{{ $sub->subhead_code ?? 'SH-'.$sub->id }}</div>
                                    
                                    {{-- FIX 2: Subhead Description --}}
                                    @if($sub->description)
                                        <div class="text-sm text-slate-500 mt-1 italic leading-tight max-w-xs">
                                            {{ $sub->description }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-8 py-5 font-medium text-gray-600 font-bold">₦{{ number_format($subTotalProv, 2) }}</td>
                                
                                <td class="px-8 py-5 font-black text-emerald-800">
                                    ₦{{ number_format($subSpent, 2) }}
                                </td>

                                <td class="px-8 py-5">
                                    <span class="px-4 py-4 rounded-full text-m font-black {{ $balance < 0 ? 'bg-red-100 text-red-700 shadow-sm shadow-red-100' : 'bg-yellow-100 text-yellow-800 shadow-sm shadow-yellow-100' }}">
                                        ₦{{ number_format($balance, 2) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-8 py-10 text-center">
                                    <p class="text-gray-400 italic text-sm font-medium">No subheads found for the {{ $activeCategory }} category in this MDA.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- Empty State --}}
        <div class="py-20 flex flex-col items-center justify-center bg-emerald-50/30 rounded-3xl border-2 border-dashed border-emerald-100">
            <div class="bg-white p-6 rounded-full shadow-lg mb-4 animate-bounce">
                <img src="{{ asset('assets/images/katsina-crest.png') }}" class="h-16 w-auto">
            </div>
            <p class="text-emerald-800 font-black uppercase tracking-widest text-sm">Accessing Budget Explorer</p>
            <p class="text-gray-500 text-xs mt-2 italic font-medium">Please select an MDA from the top bar to analyze fiscal performance.</p>
        </div>
    @endif
</div>