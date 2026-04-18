<div class="relative space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="serif text-3xl text-slate-900 uppercase">Expenditure Tracking</h2>
            <div class="flex items-center gap-3 mt-1">
                <p class="text-[10px] font-black text-emerald-800 uppercase tracking-widest">Live Financial Release Ledger</p>
                <span class="text-slate-300">|</span>
                
                {{-- Records Per Page Control --}}
                <div class="flex items-center gap-2">
                    <span class="text-[9px] font-black text-slate-400 uppercase">Show:</span>
                    <select wire:model.live="perPage" class="bg-slate-100 border-none text-[10px] font-black text-emerald-700 uppercase focus:ring-1 focus:ring-emerald-500 rounded-lg px-2 py-1 cursor-pointer">
                        <option value="10">10 Records</option>
                        <option value="20">20 Records</option>
                        <option value="50">50 Records</option>
                        <option value="100">100 Records</option>
                        <option value="500">500 Records</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.expenditure.template') }}" class="px-6 py-3 bg-slate-800 text-slate-200 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 hover:bg-slate-700 transition-all shadow-sm">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2.5"/></svg>
                Download Template
            </a>

            {{-- Add this button next to the Export PDF button in expenditure-tracking.blade.php --}}

            <button wire:click="exportCSV" class="px-6 py-3 bg-white border border-slate-200 text-slate-700 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 hover:bg-slate-50 transition-all shadow-sm">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Export CSV
            </button>

            <button wire:click="exportPDF" class="px-6 py-3 bg-white border border-slate-200 text-slate-700 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 hover:bg-slate-50 transition-all shadow-sm">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-width="2.5"/></svg>
                Export PDF
            </button>

            <a href="{{ route('admin.expenditure.upload') }}" class="px-6 py-3 bg-emerald-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 hover:bg-emerald-800 transition-all shadow-lg shadow-emerald-900/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" stroke-width="2.5"/></svg>
                Upload Release
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="md:col-span-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">General Search</label>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by MDA, Code, Ref or Narration..." class="w-full bg-slate-50 border-none rounded-xl px-5 py-3 text-sm focus:ring-2 focus:ring-emerald-500 transition-all font-medium">
            </div>

            <div>
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Date From</label>
                <input wire:model.live="dateFrom" type="date" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3 text-sm focus:ring-2 focus:ring-emerald-500">
            </div>

            <div>
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Date To</label>
                <input wire:model.live="dateTo" type="date" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3 text-sm focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6 pt-6 border-t border-slate-50">
            <div>
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Release Status</label>
                <select wire:model.live="status" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3 text-sm font-bold uppercase cursor-pointer">
                    <option value="all">All Records</option>
                    <option value="active">Active Only</option>
                    <option value="cancelled">Cancelled Only</option>
                </select>
            </div>

            <div>
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Min Amount (₦)</label>
                <input wire:model.live="minAmount" type="number" placeholder="0.00" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3 text-sm">
            </div>

            <div class="flex items-end">
                <button wire:click="clearFilters" class="w-full py-3 text-[10px] font-black uppercase tracking-widest text-rose-500 hover:bg-rose-50 rounded-xl transition-all">
                    Reset All Filters
                </button>
            </div>

            <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex flex-col justify-center text-right">
                <p class="text-[8px] font-black text-emerald-800 uppercase tracking-widest">Total Filtered Amount</p>
                <h4 class="text-lg font-black text-emerald-900">₦ {{ number_format($totalFilteredAmount, 2) }}</h4>
            </div>
        </div>
    </div>

    {{-- Main Table Section --}}
    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
        {{-- Results Counter --}}
        <div class="px-8 py-4 bg-slate-50/50 border-b border-slate-100 flex justify-between items-center">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                Showing {{ $releases->firstItem() ?? 0 }} to {{ $releases->lastItem() ?? 0 }} of {{ $releases->total() }} results
            </span>
        </div>

        <div class="overflow-x-auto max-h-[70vh] overflow-y-auto">
            <table class="w-full text-left border-separate border-spacing-0">
                <thead class="sticky top-0 z-10 bg-slate-100">
                    <tr>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest border-b border-slate-200">Date / Ref</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest border-b border-slate-200">MDA / Subhead</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest border-b border-slate-200">Narration</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right border-b border-slate-200">Amount</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center border-b border-slate-200">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($releases as $release)
                    <tr class="hover:bg-slate-50/50 transition-colors {{ $release->is_cancelled ? 'opacity-50 grayscale bg-rose-50/20' : '' }}">
                        <td class="px-8 py-5">
                            <div class="text-[11px] font-bold text-slate-900">{{ \Carbon\Carbon::parse($release->release_date)->format('d M, Y') }}</div>
                            <div class="text-[9px] font-mono font-black text-slate-400 mt-1 uppercase tracking-tighter">{{ $release->reference_no }}</div>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex flex-col gap-1">
                                <div class="text-[11px] font-black text-slate-900 uppercase leading-tight mb-1">
                                    {{ $release->mda->name ?? 'Unknown MDA' }}
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[9px] font-black px-1.5 py-0.5 bg-slate-100 rounded text-slate-500 uppercase">{{ $release->mda_code }}</span>
                                    <span class="text-[9px] font-black px-1.5 py-0.5 bg-emerald-50 rounded text-emerald-700 uppercase">{{ $release->subhead_code }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            <p class="text-[11px] font-bold text-slate-800 uppercase tracking-tight leading-snug">
                                {{ $release->narration }}
                            </p>
                            @if($release->is_cancelled)
                            <span class="text-[8px] font-black text-rose-600 uppercase mt-1 block tracking-widest">Cancelled: {{ $release->cancelled_reason }}</span>
                            @endif
                        </td>
                        <td class="px-8 py-5 text-right whitespace-nowrap">
                            <span class="text-sm font-black {{ $release->is_cancelled ? 'line-through text-slate-400' : 'text-slate-900 font-mono' }}">
                                ₦ {{ number_format($release->amount, 2) }}
                            </span>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <div class="flex items-center justify-center gap-2">
                                {{-- Changed from Link to wire:click for Modal --}}
                                <button wire:click="editRelease({{ $release->id }})" class="p-2 text-slate-400 hover:text-emerald-600 transition-colors" title="Edit Entry">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2.5"/></svg>
                                </button>
                                
                                <button 
                                    wire:click="deleteRelease({{ $release->id }})" 
                                    wire:confirm="Permanent Delete: Are you sure? This will affect your budget balances."
                                    class="p-2 text-slate-400 hover:text-rose-600 transition-colors" 
                                    title="Delete Entry"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2.5"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-8 py-20 text-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">No records found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-6 bg-slate-50/50 border-t border-slate-100">
            {{ $releases->links() }}
        </div>
    </div>

    {{-- Edit Release Modal --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" wire:click="$set('showEditModal', false)"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-[2.5rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100">
                <div class="px-8 py-6 bg-slate-50/80 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="serif text-xl text-slate-900 uppercase">Edit Record</h3>
                    <button wire:click="$set('showEditModal', false)" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="updateRelease" class="p-8 space-y-5">
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Reference Number</label>
                        <input wire:model="edit_reference_no" type="text" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-mono focus:ring-2 focus:ring-emerald-500">
                        @error('edit_reference_no') <span class="text-rose-500 text-[10px] font-bold mt-1 uppercase">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Release Date</label>
                            <input wire:model="edit_release_date" type="date" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500">
                            @error('edit_release_date') <span class="text-rose-500 text-[10px] font-bold mt-1 uppercase">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Amount (₦)</label>
                            <input wire:model="edit_amount" type="number" step="0.01" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-emerald-500">
                            @error('edit_amount') <span class="text-rose-500 text-[10px] font-bold mt-1 uppercase">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Narration</label>
                        <textarea wire:model="edit_narration" rows="3" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
                        @error('edit_narration') <span class="text-rose-500 text-[10px] font-bold mt-1 uppercase">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" wire:click="$set('showEditModal', false)" class="flex-1 py-4 text-[10px] font-black uppercase text-slate-400 tracking-widest hover:bg-slate-50 rounded-2xl transition-all">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 py-4 bg-emerald-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-900/20 hover:bg-emerald-800 transition-all">
                            <span wire:loading.remove wire:target="updateRelease">Save Changes</span>
                            <span wire:loading wire:target="updateRelease">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>