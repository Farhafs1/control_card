<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">MDA Management</h2>
            <p class="text-[10px] text-emerald-600 font-bold uppercase tracking-[0.2em]">Katsina State Ministry of Budget</p>
        </div>
        <div class="flex space-x-3">
            <button 
                type="button"
                onclick="confirmReset()"
                class="bg-red-50 text-red-600 border border-red-100 px-4 py-2.5 rounded-xl text-[10px] font-black hover:bg-red-100 transition uppercase tracking-widest"
            >
                Reset Database
            </button>

            <button wire:click="downloadTemplate" class="flex items-center space-x-2 bg-white border border-slate-200 text-slate-600 px-4 py-2.5 rounded-xl text-xs font-bold hover:bg-slate-50 transition">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Template</span>
            </button>
            <button wire:click="$toggle('showForm')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl shadow-lg shadow-emerald-900/20 transition text-sm font-bold">
                {{ $showForm ? '✕ Close' : '+ New MDA' }}
            </button>
        </div>
    </div>

    @if(session()->has('message'))
        <div class="bg-emerald-900 text-emerald-100 p-4 rounded-2xl flex items-center space-x-3 animate-in fade-in slide-in-from-top-2">
            <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <span class="text-xs font-bold uppercase tracking-wide">{{ session('message') }}</span>
        </div>
    @endif

    @if(session()->has('error') || !empty($importErrors))
        <div class="bg-red-50 border border-red-200 p-5 rounded-2xl animate-in fade-in zoom-in-95">
            <div class="flex items-center space-x-3 mb-3 text-red-800">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                <span class="font-black text-xs uppercase tracking-widest">{{ session('error') ?? 'Import Encountered Issues' }}</span>
            </div>
            @if(!empty($importErrors))
                <div class="max-h-32 overflow-y-auto">
                    <ul class="text-[10px] font-bold text-red-600 space-y-1 list-disc list-inside">
                        @foreach($importErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    @if($showForm)
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200 animate-in fade-in zoom-in-95">
        <h3 class="text-xs font-black text-emerald-600 uppercase tracking-widest mb-6 border-b border-emerald-50 pb-2">
            {{ $editingMdaId ? 'Edit MDA Profile' : 'Register New MDA' }}
        </h3>
        <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Admin Code (MDA Code)</label>
                <input type="text" wire:model="mda_code" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none border transition font-bold text-emerald-900">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Ministry/Agency Name</label>
                <input type="text" wire:model="name" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none border transition">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Sector</label>
                <select wire:model="sector" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none border transition">
                    <option value="">Choose Sector...</option>
                    <option value="Administrative">Administrative</option>
                    <option value="Economic">Economic</option>
                    <option value="Social">Social</option>
                    <option value="Law & Justice">Law & Justice</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Secret Code</label>
                <input type="text" wire:model="mda_secret_code" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none border transition">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Assigned Officer</label>
                <select wire:model="user_id" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none border transition">
                    <option value="">Select Officer...</option>
                    @foreach($officers as $officer)
                        <option value="{{ $officer->id }}">{{ $officer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-emerald-900 text-white py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-emerald-800 transition shadow-lg shadow-emerald-900/20">
                    {{ $editingMdaId ? 'Update MDA' : 'Save MDA Profile' }}
                </button>
            </div>
        </form>
    </div>
    @endif

    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/30 flex flex-col lg:flex-row justify-between items-center gap-4">
            <div class="relative max-w-md w-full">
                <input type="text" wire:model.live="search" placeholder="Search by name or Admin Code..." class="w-full border-slate-200 rounded-2xl p-3 pl-10 text-xs focus:ring-2 focus:ring-emerald-500 outline-none border font-medium">
                <svg class="w-4 h-4 absolute left-3 top-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <div class="flex items-center bg-white border border-dashed border-slate-300 rounded-2xl p-1 px-3 min-w-[300px]">
                <div class="flex-1 relative">
                    <input type="file" wire:model="bulk_file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <div class="text-[10px] font-bold text-slate-500 truncate max-w-[200px]">
                        {{ $bulk_file ? $bulk_file->getClientOriginalName() : 'CSV Bulk Import' }}
                    </div>
                </div>
                @if($bulk_file)
                    <button wire:click="importMdas" wire:loading.attr="disabled" class="ml-2 bg-emerald-100 text-emerald-700 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-emerald-200 transition">
                        <span wire:loading.remove wire:target="importMdas">Process</span>
                        <span wire:loading wire:target="importMdas italic">...</span>
                    </button>
                @endif
            </div>
        </div>

        <table class="min-w-full">
            <thead>
                <tr class="bg-slate-50/50">
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Admin Code</th>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">MDA Details</th>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Sector</th>
                    <th class="px-8 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                    <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($mdas as $mda)
                <tr class="hover:bg-emerald-50/20 transition group">
                    <td class="px-8 py-5">
                        <span class="text-xs font-mono font-black text-emerald-800 bg-emerald-100 px-2 py-1 rounded-md">{{ $mda->mda_code }}</span>
                    </td>
                    <td class="px-8 py-5">
                        <div class="text-sm font-bold text-slate-800 uppercase">{{ $mda->name }}</div>
                        <div class="text-[10px] text-slate-400 font-medium tracking-tight">
                            SECRET: <span class="text-slate-600">{{ $mda->mda_secret_code ?? 'UNSET' }}</span> | 
                            OFFICER: <span class="text-emerald-600 font-bold">{{ $mda->user->name ?? 'NOT ASSIGNED' }}</span>
                        </div>
                    </td>
                    <td class="px-8 py-5">
                        <span class="text-[10px] font-bold text-slate-500 uppercase">{{ $mda->sector }}</span>
                    </td>
                    <td class="px-8 py-5 text-center">
                        <button wire:click="toggleStatus({{ $mda->id }})" class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-tighter transition {{ $mda->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                            {{ $mda->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </td>
                    <td class="px-8 py-5 text-right space-x-4">
                        <button wire:click="edit({{ $mda->id }})" class="text-slate-400 hover:text-emerald-600 transition font-black text-[10px] uppercase tracking-widest">Edit</button>
                        <button 
                            type="button"
                            onclick="confirmDelete({{ $mda->id }}, '{{ $mda->name }}')" 
                            class="text-slate-300 hover:text-red-600 transition font-black text-[10px] uppercase tracking-widest"
                        >
                            Delete
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-20 text-center">
                        <div class="text-slate-300 mb-2 font-black text-xs uppercase tracking-[0.3em]">No Data Found</div>
                        <p class="text-slate-400 text-xs italic font-medium">Try adjusting your search or uploading a CSV template.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-6 bg-slate-50/50 border-t border-slate-100">
            {{ $mdas->links() }}
        </div>
    </div>
</div>
<script>
    function confirmReset() {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete all MDA records and reset the database!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#065f46', // Emerald 800
            cancelButtonColor: '#94a3b8',  // Slate 400
            confirmButtonText: 'Yes, reset it!',
            cancelButtonText: 'Cancel',
            background: '#ffffff',
            borderRadius: '24px',
            customClass: {
                title: 'text-xs uppercase font-black tracking-widest text-slate-800',
                htmlContainer: 'text-[10px] font-bold text-slate-500 uppercase',
                confirmButton: 'rounded-xl px-6 py-3 text-xs font-black uppercase tracking-widest',
                cancelButton: 'rounded-xl px-6 py-3 text-xs font-black uppercase tracking-widest'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // This calls the Livewire function
                @this.resetAllMdas();
            }
        })
    }

    function confirmDelete(id, name) {
        Swal.fire({
            title: 'Delete MDA?',
            text: `Are you sure you want to remove ${name}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#991b1b', // Red 800
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Yes, delete it',
            customClass: {
                title: 'text-xs uppercase font-black tracking-widest text-slate-800',
                htmlContainer: 'text-[10px] font-bold text-slate-400 uppercase',
                confirmButton: 'rounded-xl px-4 py-2 text-[10px] font-black uppercase tracking-widest',
                cancelButton: 'rounded-xl px-4 py-2 text-[10px] font-black uppercase tracking-widest'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                @this.delete(id);
            }
        })
    }

    window.addEventListener('swal:toast', event => {
        Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
            background: '#064e3b', // Emerald 900
            color: '#fff'
        }).fire({
            icon: 'success',
            title: event.detail
        });
    });
</script>