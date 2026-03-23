@php
    // Use 'type' column and perform fuzzy matching to determine the theme
    $rawType = strtolower($subhead->category->type ?? 'default');
    
    if (str_contains($rawType, 'cap')) {
        $key = 'capital';
    } elseif (str_contains($rawType, 'over')) {
        $key = 'overhead';
    } elseif (str_contains($rawType, 'pers')) {
        $key = 'personnel';
    } elseif (str_contains($rawType, 'rev')) {
        $key = 'revenue';
    } else {
        $key = 'default';
    }

    $themeClasses = match($key) {
        'capital' => [
            'bg' => 'bg-emerald-50', 
            'bg-hover' => 'hover:bg-emerald-50/50',
            'border' => 'border-emerald-100', 
            'accent' => 'text-emerald-700',
            'button' => 'bg-emerald-600 hover:bg-emerald-500',
            'ring' => 'focus:ring-emerald-500'
        ],
        'overhead' => [
            'bg' => 'bg-amber-50', 
            'bg-hover' => 'hover:bg-amber-50/50',
            'border' => 'border-amber-100', 
            'accent' => 'text-amber-700',
            'button' => 'bg-amber-600 hover:bg-amber-500',
            'ring' => 'focus:ring-amber-500'
        ],
        'personnel' => [
            'bg' => 'bg-orange-50', 
            'bg-hover' => 'hover:bg-orange-50/50',
            'border' => 'border-orange-100', 
            'accent' => 'text-orange-700',
            'button' => 'bg-orange-600 hover:bg-orange-500',
            'ring' => 'focus:ring-orange-500'
        ],
        'revenue' => [
            'bg' => 'bg-blue-50', 
            'bg-hover' => 'hover:bg-blue-50/50',
            'border' => 'border-blue-100', 
            'accent' => 'text-blue-700',
            'button' => 'bg-blue-600 hover:bg-blue-500',
            'ring' => 'focus:ring-blue-500'
        ],
        default => [
            'bg' => 'bg-slate-50', 
            'bg-hover' => 'hover:bg-slate-50/50',
            'border' => 'border-slate-100', 
            'accent' => 'text-slate-700',
            'button' => 'bg-slate-900 hover:bg-slate-800',
            'ring' => 'focus:ring-slate-900'
        ],
    };
@endphp

<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <nav class="flex items-center gap-4 mb-2">
                <a href="{{ route('admin.subheads.show', $subhead->mda_id) }}" 
                   class="group flex items-center justify-center w-8 h-8 rounded-full bg-white border border-slate-200 shadow-sm hover:border-slate-300 transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>

                <ol class="inline-flex items-center space-x-2 text-[10px] font-black uppercase tracking-widest">
                    <li><a href="{{ route('admin.subheads') }}" class="text-slate-400 hover:text-slate-600 transition-colors">MDAs</a></li>
                    <li class="text-slate-300">/</li>
                    <li><a href="{{ route('admin.subheads.show', $subhead->mda_id) }}" class="text-slate-400 hover:text-slate-600 transition-colors">{{ $subhead->mda->name }}</a></li>
                    <li class="text-slate-300">/</li>
                    <li class="text-slate-500">{{ $subhead->subhead_code }}</li>
                </ol>
            </nav>
            <div class="flex items-baseline gap-3">
                <span class="font-mono text-2xl font-black px-3 py-1 rounded-xl border {{ $themeClasses['accent'] }} {{ $themeClasses['bg'] }} {{ $themeClasses['border'] }}">
                    {{ $subhead->subhead_code }}
                </span>
                <h2 class="serif text-2xl text-slate-600 tracking-tight uppercase">{{ $subhead->description }}</h2>
            </div>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-2 px-1">
                {{ $subhead->mda->name }} • <span class="{{ $themeClasses['accent'] }} font-black">{{ $subhead->category->type ?? 'General' }}</span>
            </p>
        </div>
        
        <div class="bg-slate-900 px-8 py-5 rounded-[2.5rem] shadow-xl flex items-center gap-8 border border-white/5">
            {{-- Approved Provision --}}
            <div class="border-r border-white/10 pr-8">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] mb-1">Approved Provision</p>
                <p class="text-xl font-mono font-bold text-slate-300">₦{{ number_format($subhead->total_budget, 2) }}</p>
            </div>

            {{-- Unspent Balance --}}
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Unspent Balance</p>
                    {{-- Percentage Badge --}}
                    <span class="text-[8px] font-black px-1.5 py-0.5 rounded bg-white/10 {{ $statusColor }}">
                        {{ number_format($percentLeft, 1) }}%
                    </span>
                </div>
                <p class="text-2xl font-mono font-bold text-white">₦{{ number_format($balance, 2) }}</p>
            </div>

            <!-- {{-- Add Button --}}
            <button wire:click="$set('showReleaseModal', true)" class="{{ $themeClasses['button'] }} text-white p-3 rounded-2xl transition-all duration-300 shadow-lg group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
            </button> -->
        </div>
    </div>

    {{-- Bin Card Table --}}
    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-separate border-spacing-0">
                <thead>
                    <tr class="{{ $themeClasses['bg'] }} transition-colors">
                        <th class="px-6 py-5 text-[10px] font-black {{ $themeClasses['accent'] }} opacity-100 uppercase tracking-widest border-b {{ $themeClasses['border'] }}">S/N</th>
                        <th class="px-6 py-5 text-[10px] font-black {{ $themeClasses['accent'] }} opacity-100 uppercase tracking-widest border-b {{ $themeClasses['border'] }}">Date</th>
                        <th class="px-6 py-5 text-[10px] font-black {{ $themeClasses['accent'] }} opacity-100 uppercase tracking-widest border-b {{ $themeClasses['border'] }}">Reference No.</th>
                        <th class="px-6 py-5 text-[10px] font-black {{ $themeClasses['accent'] }} opacity-100 uppercase tracking-widest text-right border-b {{ $themeClasses['border'] }}">Amount Released</th>
                        <th class="px-6 py-5 text-[10px] font-black {{ $themeClasses['accent'] }} opacity-100 uppercase tracking-widest text-right border-b {{ $themeClasses['border'] }}">Total Amount Released</th>
                        <th class="px-6 py-5 text-[10px] font-black {{ $themeClasses['accent'] }} opacity-100 uppercase tracking-widest text-center border-b {{ $themeClasses['border'] }}">Actions</th>
                        <th class="px-8 py-5 text-[10px] font-black {{ $themeClasses['accent'] }} uppercase tracking-widest text-right border-b {{ $themeClasses['border'] }} {{ $themeClasses['bg'] }} border-l">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y {{ $themeClasses['border'] }}">
                    @php 
                        $currentTotalReleased = $initialTotalReleased;
                        $currentBalance = $initialBalance;
                    @endphp

                    @forelse($releases as $index => $release)
                        @php
                            if(!$release->is_cancelled) {
                                $currentTotalReleased += $release->amount;
                                $currentBalance -= $release->amount;
                            }
                        @endphp

                        @if(isset($editingReleaseId) && $editingReleaseId === $release->id)
                            {{-- EDIT MODE ROW --}}
                            <tr class="{{ $themeClasses['bg'] }} transition-colors">
                                <td class="px-6 py-4 text-xs font-bold {{ $themeClasses['accent'] }} opacity-50">
                                    {{ $releases->firstItem() + $index }}
                                </td>
                                <td class="px-2 py-2">
                                    <input type="date" wire:model="editForm.release_date" 
                                        class="w-full bg-white border-none rounded-lg text-xs p-2 focus:ring-2 {{ $themeClasses['ring'] }} {{ $themeClasses['accent'] }}">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="text" wire:model="editForm.reference_no" 
                                        class="w-full bg-white border-none rounded-lg text-xs p-2 focus:ring-2 {{ $themeClasses['ring'] }} {{ $themeClasses['accent'] }} font-bold uppercase">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="number" step="0.01" wire:model="editForm.amount" 
                                        class="w-full bg-white border-none rounded-lg text-xs p-2 focus:ring-2 {{ $themeClasses['ring'] }} {{ $themeClasses['accent'] }} font-bold text-right">
                                </td>
                                <td colspan="2" class="px-6 py-4">
                                    <div class="flex justify-center gap-2">
                                        <button wire:click="updateRelease" class="p-1.5 {{ $themeClasses['button'] }} text-white rounded-lg shadow-md transition-transform active:scale-90">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                        </button>
                                        <button wire:click="cancelEdit" class="p-1.5 bg-slate-200 text-slate-600 rounded-lg hover:bg-slate-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-8 py-5 border-l {{ $themeClasses['border'] }} {{ $themeClasses['bg'] }}"></td>
                            </tr>
                        @else
                            {{-- VIEW MODE ROW --}}
                            <tr class="group {{ $themeClasses['bg-hover'] }} transition-colors {{ $release->is_cancelled ? 'bg-rose-50/30' : '' }}">
                                <td class="px-6 py-5 text-xs font-bold {{ $themeClasses['accent'] }}">{{ $releases->firstItem() + $index }}</td>
                                <td class="px-6 py-5 text-xs font-medium {{ $themeClasses['accent'] }}">{{ \Carbon\Carbon::parse($release->release_date)->format('d/m/Y') }}</td>
                                <td class="px-6 py-5 text-xs font-bold {{ $themeClasses['accent'] }} uppercase">{{ $release->reference_no }}</td>
                                <td class="px-6 py-5 text-right text-xs font-bold {{ $release->is_cancelled ? 'text-slate-300 line-through' : $themeClasses['accent'] }}">
                                    {{ number_format($release->amount, 2) }}
                                </td>
                                <td class="px-6 py-5 text-right text-xs font-bold {{ $release->is_cancelled ? 'text-slate-300' : $themeClasses['accent'] }}">
                                    {{ $release->is_cancelled ? '-' : number_format($currentTotalReleased, 2) }}
                                </td>
                                <td class="px-6 py-5 text-center relative">
                                    <div class="flex items-center justify-center gap-3">
                                        @if($release->is_cancelled)
                                            <span class="px-2 py-1 rounded text-[9px] font-black bg-rose-100 text-rose-600 uppercase">Cancelled</span>
                                        @else
                                            <div class="opacity-0 group-hover:opacity-100 flex gap-3 transition-opacity duration-200">
                                                <button wire:click="editRelease({{ $release->id }})" class="{{ $themeClasses['accent'] }} hover:scale-110 transition-transform">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                                <button onclick="confirm('Delete this record?') || event.stopImmediatePropagation()" wire:click="deleteRelease({{ $release->id }})" class="text-rose-400 hover:text-rose-600 hover:scale-110 transition-transform">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                            <span class="group-hover:hidden {{ $themeClasses['accent'] }} opacity-20">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-right text-sm font-black border-l {{ $themeClasses['border'] }} {{ $themeClasses['accent'] }} {{ $themeClasses['bg'] }}">
                                    ₦{{ number_format($currentBalance, 2) }}
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-8 py-10 text-center text-[10px] font-black {{ $themeClasses['accent'] }} opacity-40 uppercase tracking-widest">
                                No transaction history found.
                            </td>
                        </tr>
                    @endforelse

                    {{-- EXCEL-STYLE GHOST ROW (ADD NEW) --}}
                    <tr class="bg-slate-50/50 border-t-2 {{ $themeClasses['border'] }}">
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-white border {{ $themeClasses['border'] }} {{ $themeClasses['accent'] }} text-xs font-black shadow-sm">+</span>
                        </td>
                        <td class="px-2 py-2">
                            <input type="date" wire:model="newRelease.release_date" 
                                class="w-full bg-white border-none rounded-lg text-xs p-2 focus:ring-2 {{ $themeClasses['ring'] }} {{ $themeClasses['accent'] }}">
                        </td>
                        <td class="px-2 py-2">
                            {{-- This field now leverages the auto-generated reference from the component --}}
                            <input type="text" wire:model="newRelease.reference_no" placeholder="REF NO..." 
                                class="w-full bg-white border-none rounded-lg text-xs p-2 focus:ring-2 {{ $themeClasses['ring'] }} {{ $themeClasses['accent'] }} font-bold uppercase placeholder:opacity-30">
                        </td>
                        <td class="px-2 py-2">
                            <input type="number" step="0.01" wire:model="newRelease.amount" placeholder="0.00" 
                                class="w-full bg-white border-none rounded-lg text-xs p-2 focus:ring-2 {{ $themeClasses['ring'] }} {{ $themeClasses['accent'] }} font-bold text-right placeholder:opacity-30">
                        </td>
                        <td colspan="2" class="px-4 py-2">
                            <button wire:click="saveNewRelease" 
                                class="w-full py-2.5 {{ $themeClasses['button'] }} text-white text-[10px] font-black uppercase rounded-xl shadow-lg active:scale-95 transition-all">
                                Add Release
                            </button>
                        </td>
                        <td class="px-8 py-5 border-l {{ $themeClasses['border'] }} {{ $themeClasses['bg'] }}"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Manual Release Modal (Legacy Option) --}}
    @if(isset($showReleaseModal) && $showReleaseModal)
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
        <div class="bg-white rounded-[3rem] shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100 animate-in fade-in zoom-in duration-300 my-8">
            <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/30 flex justify-between items-center">
                <div>
                    <h3 class="serif text-2xl text-slate-900">Add Release</h3>
                    <p class="text-[10px] font-black {{ $themeClasses['accent'] }} uppercase tracking-widest mt-1">Manual Ledger Update</p>
                </div>
                <button wire:click="$set('showReleaseModal', false)" class="text-slate-400 hover:text-rose-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <form wire:submit.prevent="saveRelease" class="p-10 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-slate-400 px-1">Date</label>
                        <input type="date" wire:model="release_date" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 {{ $themeClasses['ring'] }}">
                        @error('release_date') <span class="text-[10px] text-rose-500 px-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-slate-400 px-1">Ref No.</label>
                        {{-- Prefilled reference will also show here if the modal uses the same logic --}}
                        <input type="text" wire:model="reference_no" placeholder="REF/2026/001" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 {{ $themeClasses['ring'] }} uppercase">
                        @error('reference_no') <span class="text-[10px] text-rose-500 px-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase text-slate-400 px-1">Amount (₦)</label>
                    <input type="number" step="0.01" wire:model="amount" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-bold focus:ring-2 {{ $themeClasses['ring'] }}">
                    @error('amount') <span class="text-[10px] text-rose-500 px-1">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-black uppercase text-slate-400 px-1">Description / Narration</label>
                    <textarea wire:model="narration" rows="3" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 {{ $themeClasses['ring'] }}" placeholder="Purpose of this release..."></textarea>
                    @error('narration') <span class="text-[10px] text-rose-500 px-1">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" wire:click="$set('showReleaseModal', false)" class="flex-1 py-4 text-[10px] font-black uppercase text-slate-400 hover:bg-slate-50 rounded-2xl transition-colors">Dismiss</button>
                    <button type="submit" class="flex-1 py-4 text-[10px] font-black uppercase text-white rounded-2xl {{ $themeClasses['button'] }} transition-all shadow-lg active:scale-95">Post Release</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>