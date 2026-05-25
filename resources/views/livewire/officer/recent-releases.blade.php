<div class="space-y-8 p-4">
    {{-- Modern Header & Search Section --}}
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
        <div>
            <h2 class="serif text-4xl text-slate-900 tracking-tight">Recent Postings</h2>
            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-[0.3em] mt-2">Expenditure Tracking Ledger</p>
        </div>
        
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
            {{-- Search Bar with Loading State --}}
            <div class="relative w-full sm:w-80 group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i wire:loading.remove wire:target="search" class="fas fa-search text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                    {{-- Spinner shown only when searching --}}
                    <i wire:loading wire:target="search" class="fas fa-circle-notch fa-spin text-emerald-500"></i>
                </div>
                <input type="text" wire:model.live.debounce.400ms="search" 
                    placeholder="Search amount, code, or MDA..." 
                    class="block w-full pl-11 pr-10 py-3 bg-white border-none rounded-2xl shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-emerald-500 transition-all text-sm placeholder:text-slate-400">
                
                {{-- Clear Search Button --}}
                @if($search)
                    <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-rose-500 transition-colors">
                        <i class="fas fa-times-circle"></i>
                    </button>
                @endif
            </div>

            {{-- Status Filter --}}
            <select wire:model.live="filterStatus" 
                class="w-full sm:w-44 py-3 border-none rounded-2xl shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-emerald-500 text-sm font-medium text-slate-600">
                <option value="all">All Statuses</option>
                <option value="pending">⏳ Pending</option>
                <option value="vetted">🔍 Vetted</option>
                <option value="approved">✅ Approved</option>
                <option value="released">💰 Released</option>
            </select>
        </div>
    </div>

    {{-- Table Container --}}
    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-2xl shadow-slate-200/60 overflow-hidden">
        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1000px]">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-8 py-5 text-m font-black text-slate-700 uppercase tracking-widest border-b border-slate-100 whitespace-nowrap">Date</th>
                        <th class="px-6 py-5 text-m font-black text-slate-700 uppercase tracking-widest border-b border-slate-100 whitespace-nowrap">Reference</th>
                        <th class="px-6 py-5 text-m font-black text-slate-700 uppercase tracking-widest border-b border-slate-100 whitespace-nowrap">MDA Name & Code</th>
                        <th class="px-6 py-5 text-m font-black text-slate-700 uppercase tracking-widest border-b border-slate-100">Subhead Narration & Code</th>
                        <th class="px-8 py-5 text-m font-black text-slate-700 uppercase tracking-widest text-right border-b border-slate-100 whitespace-nowrap">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($releases as $release)
                        <tr class="hover:bg-slate-50/80 transition-all group">
                            <td class="px-8 py-6 whitespace-nowrap text-m font-bold text-slate-700">
                                {{$release->created_at->format('d M, Y')}}
                            </td>

                            <td class="px-6 py-6 min-w-[170px]">
                                <span class="px-3 py-1 bg-slate-100 rounded-lg text-sm font-bold text-slate-700 group-hover:bg-emerald-600 group-hover:text-white transition-all">
                                    {{$release->reference_no}}
                                </span>
                            </td>

                            <td class="px-6 py-6 min-w-[220px]">
                                <div class="flex flex-col">
                                    <span class="text-m font-bold text-slate-800 leading-tight">{{ $release->mda->name }}</span>
                                    <span class="text-m font-black text-emerald-600 mt-1 uppercase">{{ $release->mda->mda_code }}</span>
                                </div>
                            </td>

                            <td class="px-6 py-6">
                                <div class="flex flex-col pr-8">
                                    <span class="text-m font-medium text-slate-700 leading-relaxed">
                                        {{$release->subhead->description}}
                                    </span>
                                    <span class="text-m font-black text-emerald-600 mt-1 uppercase">{{$release->subhead->subhead_code}}</span>
                                </div>
                            </td>

                            <td class="px-8 py-6 text-right whitespace-nowrap">
                                <p class="text-m font-black text-slate-900 tracking-tighter">₦{{number_format($release->amount, 2)}}</p>
                                <span class="text-xs font-black uppercase px-2 py-2 rounded bg-slate-100 text-slate-700">{{$release->status}}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-24 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-search-minus text-slate-200 text-5xl mb-4"></i>
                                    <p class="text-slate-700 italic text-m font-medium">No results found for "{{ $search }}".</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($releases->hasPages())
            <div class="px-8 py-5 bg-slate-50/50 border-t border-slate-100">
                {{ $releases->links() }}
            </div>
        @endif
    </div>
</div>