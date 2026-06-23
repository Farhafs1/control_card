<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Staff Management</h2>
            <p class="text-[10px] text-emerald-600 font-bold uppercase tracking-[0.2em]">Personnel & Access Control</p>
        </div>
        <button wire:click="$toggle('showForm')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl shadow-lg shadow-emerald-900/20 transition text-sm font-bold flex items-center space-x-2">
            <span>{{ $showForm ? '✕ Close' : '+ Register Staff' }}</span>
        </button>
    </div>

    @if(session()->has('message'))
        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded-r-xl animate-in fade-in slide-in-from-left-4">
            <p class="text-xs font-bold">{{ session('message') }}</p>
        </div>
    @endif

    @if($showForm)
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200 animate-in zoom-in-95 duration-200">
            <h3 class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-6 border-b border-emerald-50 pb-2">
                {{ $editingUserId ? 'Edit Personnel Records' : 'Create New System Account' }}
            </h3>
            <form wire:submit.prevent="save" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Full Name</label>
                        <input type="text" wire:model.blur="name" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none border transition">
                        @error('name') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Staff ID</label>
                        <input type="text" wire:model.blur="staff_no" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none border transition font-mono">
                        @error('staff_no') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Official Email</label>
                        <input type="email" wire:model.blur="email" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none border transition">
                        @error('email') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Account Role</label>
                        <select wire:model="role" class="w-full border-gray-300 rounded-lg">
                            <option value="officer">Budget Officer</option>
                            <option value="analyst">Admin (Director/PS)</option>
                            <option value="admin">Super Admin</option>
                        </select>
                        @error('role') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">
                            {{ $editingUserId ? 'Reset Password' : 'Account Password' }}
                        </label>
                        <input type="password" wire:model.blur="password" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-emerald-500 outline-none border transition">
                        @error('password') <span class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100">
                    <button type="button" wire:click="$set('showForm', false)" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold text-xs uppercase hover:bg-slate-200 transition">Cancel</button>
                    <button type="submit" class="px-8 py-3 bg-emerald-900 text-white rounded-xl font-black text-xs uppercase tracking-widest hover:bg-emerald-800 transition shadow-lg shadow-emerald-900/20">
                        {{ $editingUserId ? 'Update Account' : 'Confirm & Create' }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/30">
            <input type="text" wire:model.live="search" placeholder="Search by name or Staff ID..." class="max-w-md w-full border-slate-200 rounded-2xl p-3 text-xs focus:ring-2 focus:ring-emerald-500 outline-none border font-medium">
        </div>

        <table class="min-w-full">
            <thead>
                <tr class="bg-slate-50/50">
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Employee</th>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">ID Number</th>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Role</th>
                    <th class="px-8 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                    <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($users as $user)
                <tr class="hover:bg-emerald-50/20 transition" wire:key="user-{{ $user->id }}">
                    <td class="px-8 py-5">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-black text-xs border border-emerald-200">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-800">{{ $user->name }}</div>
                                <div class="text-[10px] text-slate-400 font-medium">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-8 py-5 font-mono font-bold text-slate-600 text-xs">{{ $user->staff_no }}</td>
                    <td class="px-8 py-5">
                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ $user->role }}
                        </span>
                    </td>
                    <td class="px-8 py-5 text-center">
                        <button wire:click="toggleStatus({{ $user->id }})" class="px-3 py-1 rounded-full text-[9px] font-black uppercase transition {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </td>
                    <td class="px-8 py-5 text-right space-x-4">
                        @if($user->role === 'officer')
                            <button wire:click="openAssignmentModal({{ $user->id }})" class="text-emerald-600 font-black text-[10px] uppercase tracking-widest hover:underline">
                                Assign MDAs
                            </button>
                        @endif
                        {{-- Inside the Action column in your table --}}
                        <button wire:click="edit({{ $user->id }})" class="text-slate-400 hover:text-emerald-600 transition font-bold text-[10px] uppercase">Edit</button>

                        <button 
                            wire:click="deleteUser({{ $user->id }})" 
                            wire:confirm="Are you sure you want to delete this user?"
                            class="text-red-400 hover:text-red-600 transition font-bold text-[10px] uppercase ml-3">
                            Delete
                        </button>
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-6 bg-slate-50/50 border-t border-slate-100">
            {{ $users->links() }}
        </div>
    </div>

    <div x-data="{ 
            open: false,
            selectAllVisible(ids) {
                let current = [...this.$wire.selectedMdas];
                let allIncluded = ids.every(id => current.includes(id.toString()));
                if (allIncluded) {
                    this.$wire.selectedMdas = current.filter(id => !ids.includes(parseInt(id)));
                } else {
                    ids.forEach(id => { if(!current.includes(id.toString())) current.push(id.toString()); });
                    this.$wire.selectedMdas = current;
                }
            }
         }" 
         x-on:open-modal.window="if($event.detail == 'assignment-modal') open = true" 
         x-on:close-modal.window="open = false"
         x-show="open"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" x-show="open" @click="open = false"></div>

            <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl relative z-10 overflow-hidden animate-in zoom-in-95 duration-200">
                
                <div wire:loading wire:target="openAssignmentModal" class="p-20 text-center">
                    <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-emerald-500 border-t-transparent"></div>
                    <p class="mt-4 text-[10px] font-black uppercase text-slate-400 tracking-widest">Fetching Personnel Data...</p>
                </div>

                <div wire:loading.remove wire:target="openAssignmentModal">
                    @if($viewingUser)
                        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-widest text-slate-800">MDA Assignment</h3>
                                <p class="text-[9px] font-bold text-emerald-600 mt-1">STAFF: {{ $viewingUser->name }}</p>
                            </div>
                            <button @click="open = false" class="text-slate-400 hover:text-red-500">✕</button>
                        </div>

                        @php
                            $mdasList = \App\Models\Mda::where(function($q) use ($mdaSearch) {
                                            $q->where('name', 'like', '%'.$mdaSearch.'%')
                                              ->orWhere('mda_code', 'like', '%'.$mdaSearch.'%');
                                        })->orderBy('mda_code')->get();
                            $visibleIds = $mdasList->pluck('id')->toArray();
                        @endphp

                        <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center space-x-3">
                            <input type="text" wire:model.live="mdaSearch" placeholder="Filter MDAs..." class="flex-1 bg-white border-slate-200 rounded-xl p-3 text-[10px] font-bold uppercase tracking-widest focus:ring-2 focus:ring-emerald-500 outline-none border transition">
                            <button type="button" @click="selectAllVisible({{ json_encode($visibleIds) }})" class="px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 text-[10px] font-black uppercase border border-emerald-100 hover:bg-emerald-100 transition whitespace-nowrap">
                                Toggle All Visible
                            </button>
                        </div>

                        <div class="p-6 max-h-[40vh] overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @forelse($mdasList as $mda)
                                    <label wire:key="mda-{{ $mda->id }}" class="flex items-center p-3 rounded-xl border border-slate-100 hover:bg-emerald-50 transition cursor-pointer group">
                                        <input type="checkbox" wire:model="selectedMdas" value="{{ $mda->id }}" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        <div class="ml-3 overflow-hidden">
                                            <p class="text-[10px] font-black text-emerald-800 leading-none">{{ $mda->mda_code }}</p>
                                            <p class="text-[10px] font-bold text-slate-500 uppercase truncate" title="{{ $mda->name }}">{{ $mda->name }}</p>
                                        </div>
                                    </label>
                                @empty
                                    <div class="col-span-2 py-12 text-center text-slate-400 text-[10px] font-black uppercase tracking-widest">No MDAs found.</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                <span class="text-emerald-600">{{ count($selectedMdas) }}</span> Selected
                            </div>
                            <div class="flex space-x-3">
                                <button @click="open = false" class="px-6 py-2.5 text-[10px] font-black uppercase text-slate-400">Cancel</button>
                                <button wire:click="saveAssignments" class="bg-emerald-900 text-white px-8 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-900/20">
                                    Confirm Changes
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="p-20 text-center text-red-400 text-[10px] font-black uppercase">Failed to load user records.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>