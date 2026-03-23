<div class="p-4"> {{-- SINGLE ROOT ELEMENT --}}

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-700 text-[10px] font-black uppercase tracking-widest">
            {{ session('error') }}
        </div>
    @endif

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 text-[10px] font-black uppercase tracking-widest">
            {{ session('message') }}
        </div>
    @endif

    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="serif text-3xl text-slate-900 uppercase">Release Management</h2>
                <p class="text-[10px] font-black text-emerald-800 uppercase tracking-widest mt-1">Batch Upload & Record Control</p>
            </div>
            
            <div class="flex items-center gap-3">
                {{-- Aesthetic Truncate Button --}}
                <button 
                    wire:confirm="CRITICAL: This will permanently delete ALL expenditure records and pending flags. Proceed?"
                    wire:click="truncateExpenditure" 
                    class="px-4 py-3 bg-white border border-rose-100 text-rose-500 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-rose-50 hover:border-rose-200 transition-all shadow-sm flex items-center gap-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Wipe Ledger
                </button>

                <a href="{{ route('admin.expenditure') }}" class="px-6 py-3 bg-white border border-slate-200 text-slate-700 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all shadow-sm">
                    Back to Ledger
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Left: Upload Form --}}
            <div class="lg:col-span-1">
                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm">
                    <form wire:submit.prevent="processImport" class="space-y-6">
                        <div 
                            x-data="{ isUploading: false, progress: 0 }" 
                            x-on:livewire-upload-start="isUploading = true"
                            x-on:livewire-upload-finish="isUploading = false"
                            x-on:livewire-upload-error="isUploading = false"
                            x-on:livewire-upload-progress="progress = $event.detail.progress"
                        >
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4 block">Select Expenditure CSV (DD/MM/YYYY)</label>
                            
                            <div class="relative group">
                                <input type="file" wire:model="csvFile" class="hidden" id="csv_input">
                                <label for="csv_input" class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-3xl p-8 cursor-pointer group-hover:border-emerald-500 transition-all bg-slate-50/50">
                                    <svg class="w-8 h-8 text-slate-300 group-hover:text-emerald-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" stroke-width="2"/></svg>
                                    <span class="text-[10px] font-black text-slate-500 uppercase">{{ $csvFile ? $csvFile->getClientOriginalName() : 'Choose File' }}</span>
                                </label>
                            </div>

                            <div x-show="isUploading" class="mt-4">
                                <div class="h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 transition-all duration-200" :style="`width: ${progress}%`"></div>
                                </div>
                            </div>
                        </div>

                        @error('csvFile') <span class="text-rose-500 text-[10px] font-black uppercase tracking-tight">{{ $message }}</span> @enderror

                        <button type="submit" wire:loading.attr="disabled" class="w-full py-4 bg-emerald-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-900/20 disabled:opacity-50">
                            <span wire:loading.remove>Process Batch Upload</span>
                            <span wire:loading>Processing...</span>
                        </button>
                        
                        <a href="{{ route('admin.expenditure.template') }}" class="block text-center text-[9px] font-black text-slate-400 uppercase hover:text-emerald-700 transition-colors">
                            Download Formatting Guide
                        </a>
                    </form>
                </div>
            </div>

            {{-- Right: Recent Transactions --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-50 bg-slate-50/50">
                        <h3 class="text-[10px] font-black text-slate-900 uppercase tracking-widest">Confirmed Transactions</h3>
                    </div>
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-50">
                                <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase">Ref / Date (DD/MM)</th>
                                <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase">Amount</th>
                                <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($recentReleases as $release)
                            <tr class="hover:bg-slate-50/50 transition-all">
                                <td class="px-6 py-4">
                                    <div class="text-[11px] font-bold text-slate-900">{{ $release->reference_no }}</div>
                                    <div class="text-[9px] text-slate-400 font-mono">{{ \Carbon\Carbon::parse($release->release_date)->format('d/m/Y') }}</div>
                                </td>
                                <td class="px-6 py-4 text-[11px] font-black text-slate-900">
                                    ₦{{ number_format($release->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button 
                                        wire:confirm="Are you sure? This will permanently delete this release."
                                        wire:click="deleteRelease({{ $release->id }})" 
                                        class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/></svg>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-10 text-center text-[10px] font-black text-slate-300 uppercase italic">No records in ledger</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Redesigned Pending/Duplicates Section --}}
        @if($pendingItems->isNotEmpty())
        <div class="mt-8 bg-white rounded-[2.5rem] border-2 border-amber-100 shadow-xl shadow-amber-900/5 overflow-hidden">
            <div class="p-6 bg-amber-50 border-b border-amber-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-amber-500 rounded-full flex items-center justify-center text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-[11px] font-black text-amber-900 uppercase tracking-widest">Verification Queue</h3>
                        <p class="text-[10px] text-amber-700/70 font-bold uppercase tracking-tighter">Items matched existing records. Please review before proceeding.</p>
                    </div>
                </div>
                <span class="bg-amber-200 text-amber-900 px-3 py-1 rounded-full text-[10px] font-black">{{ $pendingItems->count() }} PENDING</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-separate border-spacing-0">
                    <thead>
                        <tr class="bg-slate-50/50">
                            <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Release Date</th>
                            <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Identifier (MDA/Sub)</th>
                            <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Ref No</th>
                            <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Amount</th>
                            <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Control</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($pendingItems as $item)
                        <tr class="hover:bg-amber-50/30 transition-colors">
                            <td class="px-6 py-4 font-mono text-[11px] text-slate-600">
                                {{ \Carbon\Carbon::parse($item->release_date)->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-[10px] font-black text-slate-900">{{ $item->mda_code }}</div>
                                <div class="text-[9px] font-bold text-slate-400">{{ $item->subhead_code }}</div>
                            </td>
                            <td class="px-6 py-4 text-[11px] font-bold text-slate-700">{{ $item->reference_no }}</td>
                            <td class="px-6 py-4 text-right font-mono text-[11px] font-black text-rose-600">
                                ₦{{ number_format($item->amount, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="confirmItem({{ $item->id }})" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-emerald-700 transition-all">Approve</button>
                                    <button wire:click="discardItem({{ $item->id }})" class="px-3 py-1.5 bg-rose-50 text-rose-600 border border-rose-100 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-rose-100 transition-all">Discard</button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div> {{-- END ROOT ELEMENT --}}