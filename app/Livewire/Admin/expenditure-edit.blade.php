<div class="p-8 max-w-2xl mx-auto">
    <div class="mb-6">
        <h2 class="serif text-3xl text-slate-900 uppercase">Edit Release</h2>
        <p class="text-[10px] font-black text-emerald-800 uppercase tracking-widest mt-1">
            {{ $release->mda->name }} | {{ $release->subhead_code }}
        </p>
    </div>

    <form wire:submit="update" class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm space-y-6">
        <div>
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Reference Number</label>
            <input wire:model="reference_no" type="text" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3 text-sm focus:ring-2 focus:ring-emerald-500 font-mono">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Release Date</label>
                <input wire:model="release_date" type="date" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3 text-sm focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Amount (₦)</label>
                <input wire:model="amount" type="number" step="0.01" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3 text-sm focus:ring-2 focus:ring-emerald-500 font-bold">
            </div>
        </div>

        <div>
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Narration</label>
            <textarea wire:model="narration" rows="3" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3 text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
        </div>

        <div class="flex items-center gap-4 pt-4">
            <button type="submit" class="flex-1 py-4 bg-emerald-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-800 transition-all shadow-lg shadow-emerald-900/20">
                Save Changes
            </button>
            <a href="{{ route('admin.expenditure') }}" class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-all">
                Cancel
            </a>
        </div>
    </form>
</div>