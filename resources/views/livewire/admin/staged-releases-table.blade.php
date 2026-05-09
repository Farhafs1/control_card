<div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <a href="{{ route('admin.data-extraction') }}" class="flex items-center space-x-2 text-slate-500 hover:text-slate-800 transition-all group">
            <div class="w-10 h-10 rounded-full bg-white border border-slate-100 flex items-center justify-center shadow-sm group-hover:shadow group-hover:scale-105 transition-all">
                <i class="fas fa-arrow-left text-xs"></i>
            </div>
            <span class="text-[10px] font-black uppercase tracking-widest">Back to Scraper</span>
        </a>

        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            {{-- Search Bar --}}
            <div class="relative flex-grow md:flex-grow-0">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="SEARCH REFERENCE OR NARRATION..." 
                    class="pl-10 pr-4 py-2.5 bg-white border-none rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-sm focus:ring-2 focus:ring-blue-500 w-full md:w-64 placeholder:text-slate-300">
            </div>

            {{-- Rows Per Page --}}
            <div class="flex items-center space-x-2 bg-white px-3 py-1.5 rounded-2xl border border-slate-50 shadow-sm">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Rows</span>
                <select wire:model.live="perPage" class="border-none bg-transparent text-slate-600 text-[10px] font-black focus:ring-0 cursor-pointer">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>

            <button wire:click="truncateQueue" 
                    wire:confirm="DANGER: This will permanently delete all records currently in the staging queue. Proceed?"
                    class="px-6 py-2.5 bg-white text-rose-500 text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-rose-500 hover:text-white transition-all border border-rose-100 shadow-sm flex items-center">
                <i class="fas fa-broom mr-2" wire:loading.remove wire:target="truncateQueue"></i>
                <i class="fas fa-circle-notch fa-spin mr-2" wire:loading wire:target="truncateQueue"></i>
                Clear Queue
            </button>
        </div>
    </div>

    {{-- Filter & Status Tabs --}}
    <div class="flex flex-wrap items-center gap-4 mb-4">
        {{-- Quality Filters --}}
        <div class="flex items-center space-x-1 bg-slate-100/50 p-1 rounded-2xl border border-slate-200/50">
            <button wire:click="$set('filter', 'all')" class="px-5 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all {{ $filter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">All Data</button>
            <button wire:click="$set('filter', 'invalid_mda')" class="px-5 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all {{ $filter === 'invalid_mda' ? 'bg-rose-500 text-white shadow-sm' : 'text-slate-500 hover:text-rose-600' }}">MDA Errors</button>
            <button wire:click="$set('filter', 'duplicates')" class="px-5 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all {{ $filter === 'duplicates' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-500 hover:text-amber-600' }}">Duplicates</button>
        </div>

        {{-- Vertical Divider --}}
        <div class="hidden md:block h-8 border-l border-slate-200"></div>

        {{-- NEW: Status Filters --}}
        <div class="flex items-center space-x-1 bg-blue-50/50 p-1 rounded-2xl border border-blue-100/50">
            <button wire:click="$set('statusFilter', 'all')" class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all {{ $statusFilter === 'all' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-400 hover:text-blue-500' }}">Any Status</button>
            <button wire:click="$set('statusFilter', 'approved')" class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all {{ $statusFilter === 'approved' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-400 hover:text-emerald-600' }}">Approved</button>
            <button wire:click="$set('statusFilter', 'circulating')" class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all {{ $statusFilter === 'circulating' ? 'bg-blue-500 text-white shadow-sm' : 'text-slate-400 hover:text-blue-600' }}">Circulating</button>
            <button wire:click="$set('statusFilter', 'returned')" class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all {{ $statusFilter === 'returned' ? 'bg-rose-500 text-white shadow-sm' : 'text-slate-400 hover:text-rose-600' }}">Returned</button>
        </div>
    </div>
    
    <!-- {{-- Filter Tabs --}}
    <div class="flex items-center space-x-1 mb-4 bg-slate-100/50 p-1 rounded-2xl w-fit border border-slate-200/50">
        <button wire:click="$set('filter', 'all')" class="px-5 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all {{ $filter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">All</button>
        <button wire:click="$set('filter', 'invalid_mda')" class="px-5 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all {{ $filter === 'invalid_mda' ? 'bg-rose-500 text-white shadow-sm' : 'text-slate-500 hover:text-rose-600' }}">MDA Errors</button>
        <button wire:click="$set('filter', 'duplicates')" class="px-5 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all {{ $filter === 'duplicates' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-500 hover:text-amber-600' }}">Duplicates</button>
    </div> -->

    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-2xl shadow-slate-200/50 overflow-hidden">
        
        <div class="px-10 py-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/30">
            <div class="flex flex-col">
                <div class="flex items-center space-x-3">
                    <div class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></div>
                    <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.3em]">Validation Ledger</h3>
                </div>
                <p class="text-[10px] text-slate-400 font-medium uppercase mt-2 tracking-tighter">
                    Verify and map incoming financial data before permanent commitment
                </p>
            </div>
            <div class="flex items-center space-x-4">
                <div wire:loading class="flex items-center space-x-2 px-4 py-2 bg-slate-900 rounded-xl">
                    <i class="fas fa-sync fa-spin text-white text-[10px]"></i>
                    <span class="text-[9px] text-white font-black uppercase tracking-widest">Processing</span>
                </div>
                <div class="px-5 py-2 bg-amber-50 border border-amber-100 text-amber-600 text-[10px] font-black rounded-xl uppercase tracking-tighter shadow-sm">
                    {{ number_format($releases->total()) }} Records Found
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50 bg-slate-50/10">
                        <th class="px-10 py-5">Release Date</th>
                        <th class="px-6 py-5">MDA Name</th> {{-- Column 2 --}}
                        <th class="px-6 py-5">Codes</th>    {{-- Column 3 --}}
                        <th class="px-6 py-5">Reference No.</th>
                        <th class="px-6 py-5">Narration / Description</th>
                        <th class="px-6 py-5 text-right">Amount (₦)</th>
                        <th class="px-10 py-5 text-right">Verification</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($releases as $release)
                        @php $status = $this->getValidationStatus($release); @endphp
                        <tr wire:key="staged-{{ $release->id }}" 
                            wire:loading.class="opacity-40 bg-slate-50 pointer-events-none"
                            wire:target="approve({{ $release->id }}), discard({{ $release->id }})"
                            class="hover:bg-slate-50/50 transition-all duration-300 group">
                            
                            {{-- 1. Date & Status Indicator --}}
                            <td class="px-10 py-6">
                                <div class="flex flex-col">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-3.5 h-3.5 rounded-full {{ $release->status === 'approved' ? 'bg-emerald-400' : ($release->status === 'returned' ? 'bg-rose-400' : 'bg-blue-400') }}" 
                                            title="Status: {{ ucfirst($release->status) }}"></div>
                                        <span class="text-xs font-bold text-slate-700 tracking-tight">
                                            {{ $release->release_date ? \Carbon\Carbon::parse($release->release_date)->format('M d, Y') : 'Unknown' }}
                                        </span>
                                        
                                    </div>
                                    <span class="text-[9px] font-medium text-slate-400 uppercase tracking-tighter">
                                        Added: {{ $release->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </td>

                            {{-- 2. MDA Name Column (Wrapped) --}}
                            <td class="px-6 py-6">
                                <div class="flex flex-col max-w-[200px]"> {{-- Constrain width to force wrapping --}}
                                    <span class="text-[10px] font-black text-slate-700 uppercase tracking-tight leading-normal whitespace-normal {{ $status['name_mismatch'] ? 'text-amber-600' : '' }}">
                                        {{ $release->mda_name ?? 'UNNAMED MDA' }}
                                    </span>
                                    @if($status['name_mismatch'])
                                        <span class="text-[8px] font-bold text-amber-500 uppercase tracking-tighter flex items-center gap-1 mt-1">
                                            <i class="fas fa-exclamation-triangle text-[7px]"></i> Alias Mismatch
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- 3. Source Codes --}}
                            <td class="px-6 py-6">
                                <div class="flex flex-col space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 w-fit rounded {{ $status['mda_exists'] ? 'bg-slate-100 text-slate-600' : 'bg-rose-50 text-rose-600 border-rose-200 animate-pulse' }} text-[10px] font-black border uppercase">
                                            MDA: {{ $release->mda_code }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 w-fit rounded {{ $status['subhead_exists'] ? 'bg-blue-50 text-blue-600' : 'bg-rose-50 text-rose-600 border-rose-200 animate-pulse' }} text-[10px] font-black border uppercase">
                                            SUB: {{ $release->subhead_code }}
                                        </span>
                                        @if($status['mda_exists'] && !$status['subhead_exists'])
                                            <i class="fas fa-link-slash text-rose-500 text-[10px]" title="Subhead not linked to this MDA"></i>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- 4. Reference No --}}
                            <td class="px-6 py-6">
                                <span class="text-[11px] font-black text-slate-700 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                                    {{ $release->reference_no }}
                                </span>
                            </td>

                            {{-- 5. Narration --}}
                            <td class="px-6 py-6">
                                <p class="text-[11px] text-slate-500 font-medium leading-relaxed max-w-xs line-clamp-2 italic" title="{{ $release->narration }}">
                                    "{{ $release->narration ?? 'No detailed narration provided' }}"
                                </p>
                            </td>

                            {{-- 6. Amount --}}
                            <td class="px-6 py-6 text-right">
                                <span class="text-sm font-black text-slate-900 tracking-tighter">
                                    {{ number_format($release->amount, 2) }}
                                </span>
                            </td>

                            {{-- 7. Verification Actions --}}
                            <td class="px-10 py-6 text-right">
                                <div class="flex flex-col items-end space-y-2">
                                    @if($status['is_duplicate'])
                                        <span class="px-2 py-1 bg-amber-100 text-amber-700 text-[8px] font-black uppercase rounded border border-amber-200 flex items-center gap-1">
                                            <i class="fas fa-copy"></i> Potential Duplicate
                                        </span>
                                    @endif

                                    <div class="flex justify-end items-center space-x-2">
                                        <button wire:click="approve({{ $release->id }})" 
                                                wire:loading.attr="disabled"
                                                @if($status['is_duplicate']) wire:confirm="This record appears to already exist in the ledger. Approve anyway?" @endif
                                                class="w-9 h-9 flex items-center justify-center {{ (!$status['mda_exists'] || !$status['subhead_exists'] || $release->status !== 'approved') ? 'text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed' : 'text-emerald-500 bg-emerald-50 hover:bg-emerald-500 hover:text-white border-emerald-100 shadow-sm' }} rounded-xl transition-all border"
                                                {{ (!$status['mda_exists'] || !$status['subhead_exists'] || $release->status !== 'approved') ? 'disabled' : '' }}>
                                            <i class="fas fa-check text-xs" wire:loading.remove wire:target="approve({{ $release->id }})"></i>
                                            <i class="fas fa-spinner fa-spin text-xs" wire:loading wire:target="approve({{ $release->id }})"></i>
                                        </button>

                                        <button wire:click="edit({{ $release->id }})" 
                                                class="w-9 h-9 flex items-center justify-center text-blue-500 bg-blue-50 hover:bg-blue-500 hover:text-white rounded-xl transition-all shadow-sm border border-blue-100">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>

                                        <button wire:click="discard({{ $release->id }})" 
                                                wire:confirm="Permanently discard this staged entry?"
                                                class="w-9 h-9 flex items-center justify-center text-rose-400 bg-rose-50 hover:bg-rose-500 hover:text-white rounded-xl transition-all shadow-sm border border-rose-100">
                                            <i class="fas fa-trash text-xs" wire:loading.remove wire:target="discard({{ $release->id }})"></i>
                                            <i class="fas fa-circle-notch fa-spin text-xs" wire:loading wire:target="discard({{ $release->id }})"></i>
                                        </button>
                                    </div>

                                    @if($release->status !== 'approved')
                                        <span class="text-[8px] font-bold text-blue-500 uppercase tracking-tighter italic">Status must be "Approved" to Commit</span>
                                    @elseif(!$status['mda_exists'] || !$status['subhead_exists'])
                                        <span class="text-[8px] font-bold text-rose-500 uppercase tracking-tighter">Fix Mapping to Approve</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-8 py-24 text-center bg-slate-50/20">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 bg-white shadow-inner rounded-full flex items-center justify-center mb-6">
                                        <i class="fas fa-clipboard-check text-3xl text-slate-200"></i>
                                    </div>
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-[0.3em]">No Records Found</h4>
                                    <p class="text-[10px] text-slate-400 mt-2 uppercase font-bold">Try adjusting your search or filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($releases->hasPages())
            <div class="px-10 py-6 bg-slate-50/50 border-t border-slate-50">
                {{ $releases->links() }}
            </div>
        @endif
    </div>

    {{-- Edit Modal --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-[70] flex items-center justify-center p-6" x-transition>
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-md transition-opacity" wire:click="$set('showEditModal', false)"></div>
        
        <div class="bg-white rounded-[3rem] shadow-2xl w-full max-w-xl overflow-hidden relative z-[80] border border-white/20">
            <div class="px-10 py-10">
                
                                
                <div class="flex justify-between items-start mb-10">
                    <div>
                        <span class="px-3 py-1 bg-blue-50 text-blue-600 text-[9px] font-black uppercase rounded-full tracking-widest border border-blue-100">Adjustment Mode</span>
                        <h2 class="text-2xl font-black text-slate-800 mt-3 tracking-tighter">Correct Staged Entry</h2>
                        <p class="text-[10px] text-slate-400 uppercase font-bold mt-1 tracking-tight">Ensure codes match your local system configurations</p>
                    </div>
                    <button wire:click="$set('showEditModal', false)" class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-widest">Target MDA Code</label>
                            <input type="text" wire:model="mda_code" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 transition-all">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-widest">Subhead Code</label>
                            <input type="text" wire:model="subhead_code" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 transition-all">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-widest">Transaction Narration</label>
                        <textarea wire:model="narration" rows="3" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm font-medium text-slate-600 focus:ring-2 focus:ring-blue-500 transition-all"></textarea>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-widest">Amount (Re-validated)</label>
                        <div class="relative">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 font-bold">₦</span>
                            <input type="number" step="0.01" wire:model="amount" class="w-full bg-slate-50 border-none rounded-2xl pl-10 pr-5 py-4 text-sm font-black text-slate-800 focus:ring-2 focus:ring-blue-500 transition-all">
                        </div>
                    </div>
                </div>

                {{-- Status Override (Manual Update) --}}
                <div class="mb-6">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Manual Status Override</label>
                    <div class="grid grid-cols-3 gap-2 bg-slate-50 p-1.5 rounded-2xl border border-slate-100">
                        <label class="cursor-pointer">
                            <input type="radio" wire:model="status" value="approved" class="hidden peer">
                            <div class="py-2 text-center rounded-xl text-[9px] font-black uppercase tracking-tighter transition-all peer-checked:bg-emerald-500 peer-checked:text-white text-slate-400 hover:bg-white">
                                Approved
                            </div>
                        </label>
                        
                        <label class="cursor-pointer">
                            <input type="radio" wire:model="status" value="circulating" class="hidden peer">
                            <div class="py-2 text-center rounded-xl text-[9px] font-black uppercase tracking-tighter transition-all peer-checked:bg-blue-500 peer-checked:text-white text-slate-400 hover:bg-white">
                                Circulating
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" wire:model="status" value="returned" class="hidden peer">
                            <div class="py-2 text-center rounded-xl text-[9px] font-black uppercase tracking-tighter transition-all peer-checked:bg-rose-500 peer-checked:text-white text-slate-400 hover:bg-white">
                                Returned
                            </div>
                        </label>
                    </div>
                    <p class="text-[8px] text-amber-500 font-bold uppercase mt-2 italic">
                        * Use this if a release has exceeded the 1-week scraper window on E-Budget.
                    </p>
                </div>

                <div class="flex items-center justify-between mt-12">
                    <button wire:click="$set('showEditModal', false)" class="text-[10px] font-black text-slate-400 uppercase hover:text-slate-600 tracking-widest transition-colors">
                        Discard Changes
                    </button>
                    <button wire:click="update" 
                        class="px-10 py-4 text-white text-[10px] font-black rounded-2xl uppercase tracking-[0.2em] shadow-xl transition-all active:scale-95 flex items-center
                        {{ $status === 'approved' ? 'bg-emerald-600 shadow-emerald-200' : ($status === 'returned' ? 'bg-rose-600 shadow-rose-200' : 'bg-slate-900 shadow-slate-200') }}">
                        <i class="fas fa-save mr-3" wire:loading.remove wire:target="update"></i>
                        <i class="fas fa-circle-notch fa-spin mr-3" wire:loading wire:target="update"></i>
                        {{ $status === 'approved' ? 'Confirm & Ready to Ledger' : 'Update Staged Record' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @push('scripts')
    <script>
        window.addEventListener('swal:modal', event => {
            let data = event.detail[0] || event.detail;
            Swal.fire({
                title: data.title,
                text: data.text,
                icon: data.type,
                confirmButtonColor: '#10b981', 
            });
        });

        const syncBtn = document.getElementById('syncBtn');
        if (syncBtn) {
            syncBtn.addEventListener('click', function() {
                const limitSelect = document.querySelector('select[wire\\:model="batchLimit"]');
                const limit = limitSelect ? limitSelect.value : 10;
                
                this.disabled = true;
                document.getElementById('progressContainer').style.display = 'block';
                document.getElementById('statusText').innerText = "Initializing Engine...";

                fetch(`/sync-records?limit=${limit}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    const progressInterval = setInterval(() => {
                        fetch('/sync-progress')
                            .then(res => res.json())
                            .then(data => {
                                const pBar = document.getElementById('progressBar');
                                const sText = document.getElementById('statusText');

                                pBar.style.width = data.percent + '%';
                                pBar.innerText = data.percent + '%';
                                sText.innerText = data.status;

                                if (data.percent >= 100) {
                                    clearInterval(progressInterval);
                                    sText.innerText = "✅ Sync Complete!";
                                    syncBtn.disabled = false;
                                    if (window.Livewire) {
                                        window.Livewire.dispatch('refreshData');
                                    }
                                }
                            })
                            .catch(err => console.error('Progress Check Failed:', err));
                    }, 2000);
                })
                .catch(err => {
                    console.error('Sync Initiation Failed:', err);
                    this.disabled = false;
                    document.getElementById('statusText').innerText = "Error starting engine.";
                });
            });
        }
    </script>
    @endpush
</div>