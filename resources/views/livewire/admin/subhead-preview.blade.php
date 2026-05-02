<div class="p-6 bg-gray-50 min-h-screen">
    {{-- Header & Search Section --}}
    <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-emerald-100">
        <h2 class="text-xl font-bold text-emerald-900 mb-4 uppercase tracking-tight">Budget Appropriation Manager</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Search Subhead/Code --}}
            <div class="group relative">
                <label class="flex items-center text-[10px] font-bold text-emerald-800 uppercase tracking-widest mb-1.5 ml-1 transition-colors group-focus-within:text-emerald-500">
                    <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    Subhead Registry
                </label>
                <div class="relative">
                    <input wire:model.live.debounce.300ms="search" type="text" 
                        placeholder="Search code or keyword..." 
                        class="w-full pl-4 pr-4 py-3 bg-gray-50 border-0 ring-1 ring-gray-200 rounded-xl shadow-inner-sm focus:ring-2 focus:ring-emerald-500 focus:bg-white text-sm transition-all duration-200 placeholder:text-gray-400">
                    
                    <!-- {{-- Decorative accent line --}}
                    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-0 h-0.5 bg-emerald-500 transition-all duration-300 group-focus-within:w-full rounded-full"></div> -->
                </div>
            </div>

            {{-- Filter by MDA --}}
            <div class="group relative">
                <label class="flex items-center text-[10px] font-bold text-emerald-800 uppercase tracking-widest mb-1.5 ml-1 transition-colors group-focus-within:text-emerald-600">
                    <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Organization / MDA
                </label>
                <div class="relative">
                    <input wire:model.live.debounce.300ms="mdaSearch" type="text" 
                        placeholder="Filter by Ministry name..." 
                        class="w-full pl-4 pr-4 py-3 bg-gray-50 border-0 ring-1 ring-gray-200 rounded-xl shadow-inner-sm focus:ring-2 focus:ring-emerald-600 focus:bg-white text-sm transition-all duration-200 placeholder:text-gray-400">
                    
                    <!-- {{-- Decorative accent line --}}
                    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-0 h-0.5 bg-emerald-600 transition-all duration-300 group-focus-within:w-full rounded-full"></div> -->
                </div>
            </div>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
             class="mb-4 p-3 bg-emerald-100 text-emerald-800 rounded-md font-medium text-sm border border-emerald-200">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-md font-medium text-sm border border-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Main Grid Table --}}
    <div class="overflow-x-auto bg-white rounded-lg shadow border border-gray-200">
        <table class="w-full border-collapse text-left text-[13px]">
            <thead class="bg-emerald-800 text-white uppercase sticky top-0">
                <tr>
                    <th class="p-3 border border-emerald-700 w-70">MDA</th>
                    <th class="p-3 border border-emerald-700 w-30">Code</th>
                    <th class="p-3 border border-emerald-700 w-100">Description</th>
                    <th class="p-3 border border-emerald-700 text-right w-30">Approved Provision</th>
                    <th class="p-3 border border-emerald-700 text-right w-30">Additional Provision</th>
                    <th class="p-3 border border-emerald-700 text-right w-30 text-orange-200">Virement</th>
                    <th class="p-3 border border-emerald-700 text-right w-30 text-purple-200">Supplementary Provision</th>
                    <th class="p-3 border border-emerald-700 text-center w-10">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($subheads as $sub)
                    <tr class="hover:bg-emerald-50 transition-colors group">
                        <td class="p-2 border text-gray-800 italic max-w-xs truncate">
                            {{ $sub->mda->name ?? 'N/A' }}
                        </td>
                        <td class="p-0 border">
                            <input type="text" value="{{ $sub->subhead_code }}" 
                                wire:blur="updateValue({{ $sub->id }}, 'subhead_code', $event.target.value)"
                                class="w-full p-2 border-none bg-transparent focus:ring-2 focus:ring-inset focus:ring-emerald-500">
                        </td>
                        <td class="p-0 border max-w-[300px]">
                            <input type="text" value="{{ $sub->description }}" 
                                title="{{ $sub->description }}" {{-- Hover tooltip --}}
                                wire:blur="updateValue({{ $sub->id }}, 'description', $event.target.value)"
                                class="w-full p-2 border-none bg-transparent focus:ring-2 focus:ring-inset focus:ring-emerald-500 uppercase font-medium truncate">
                        </td>
                        {{-- Approved Provision --}}
                        <td class="p-0 border">
                            <input type="text" value="{{ number_format($sub->approved_provision) }}" 
                                onfocus="this.value = this.value.replace(/,/g, '')"
                                wire:blur="updateValue({{ $sub->id }}, 'approved_provision', $event.target.value)"
                                class="w-full p-2 border-none bg-transparent focus:ring-2 focus:ring-inset focus:ring-emerald-500 text-right font-bold text-emerald-900">
                        </td>
                        {{-- Additional Provision --}}
                        <td class="p-0 border bg-blue-50/30">
                            <input type="text" value="{{ number_format($sub->additional_provision ?? 0) }}" 
                                onfocus="this.value = this.value.replace(/,/g, '')"
                                wire:blur="updateValue({{ $sub->id }}, 'additional_provision', $event.target.value)"
                                class="w-full p-2 border-none bg-transparent focus:ring-2 focus:ring-inset focus:ring-blue-500 text-right text-blue-700">
                        </td>
                        {{-- Virement --}}
                        <td class="p-0 border bg-orange-50/30">
                            <input type="text" value="{{ number_format($sub->virement ?? 0) }}" 
                                onfocus="this.value = this.value.replace(/,/g, '')"
                                wire:blur="updateValue({{ $sub->id }}, 'virement', $event.target.value)"
                                class="w-full p-2 border-none bg-transparent focus:ring-2 focus:ring-inset focus:ring-orange-500 text-right text-orange-700">
                        </td>
                        {{-- Supplementary --}}
                        <td class="p-0 border bg-purple-50/30">
                            <input type="text" value="{{ number_format($sub->supplementary ?? 0) }}" 
                                onfocus="this.value = this.value.replace(/,/g, '')"
                                wire:blur="updateValue({{ $sub->id }}, 'supplementary', $event.target.value)"
                                class="w-full p-2 border-none bg-transparent focus:ring-2 focus:ring-inset focus:ring-purple-500 text-right text-purple-800">
                        </td>
                        <td class="p-2 border text-center">
                            <button wire:click="deleteSubhead({{ $sub->id }})" 
                                wire:confirm="Permanently delete this budget line?"
                                class="text-red-400 hover:text-red-700 transition-colors">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-gray-400 italic">No records found matching your filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $subheads->links() }}
    </div>

    {{-- Loading Indicator --}}
    <div wire:loading class="fixed bottom-5 right-5 z-50">
        <div class="flex items-center bg-emerald-600 text-white px-4 py-2 rounded-full shadow-lg text-sm font-bold">
            <svg class="animate-spin h-4 w-4 mr-2 text-white" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processing...
        </div>
    </div>
</div>