<div class="p-4 sm:p-6 lg:p-8 bg-gray-50 min-h-screen">
    {{-- Header Section --}}
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between print:hidden">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Budget Performance Analytics</h1>
            <p class="text-sm text-gray-600">Quarterly performance monitoring and fiscal control.</p>
        </div>
        
        <div class="mt-4 md:mt-0 flex space-x-2">
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2-2v4"></path></svg>
                Print
            </button>
            
            <button wire:click="export('csv')" class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800 transition">
                Export CSV
            </button>

            <button wire:click="export('pdf')" class="inline-flex items-center px-4 py-2 bg-red-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-800 transition">
                Download PDF
            </button>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8 print:hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            {{-- Quarter Selection --}}
            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Select Fiscal Quarter</label>
                <select wire:model.live="quarter" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500 font-bold {{ $reportType === 'executive' ? 'bg-gray-50 text-gray-400' : '' }}">
                    <option value="1">1st Quarter (Jan - Mar)</option>
                    <option value="2">2nd Quarter (Apr - Jun)</option>
                    <option value="3">3rd Quarter (Jul - Sep)</option>
                    <option value="4">4th Quarter (Oct - Dec)</option>
                </select>
                @if($reportType === 'executive')
                    <span class="text-[10px] text-amber-600 font-bold">Executive mode displays full year data.</span>
                @endif
            </div>

            {{-- Report Perspective --}}
            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Report Perspective</label>
                <select wire:model.live="reportType" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500 font-bold">
                    <option value="executive">Executive Overview (Annual Multi-Quarter)</option>
                    <option value="overview">Quarterly Summary (Single Quarter)</option>
                    <option value="detailed">Detailed Performance (By Subhead)</option>
                    <option value="ranking">Performance Ranking (State-Wide List)</option>
                </select>
            </div>

            {{-- Expenditure Category Filter --}}
            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">
                    {{ $reportType === 'detailed' ? 'Expenditure Category Group' : 'Report Context' }}
                </label>
                @if($reportType === 'detailed')
                    <select wire:model.live="categoryId" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500 font-bold bg-amber-50">
                        <option value="">-- Select Master Group --</option>
                        @foreach($this->masterCategories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @elseif($reportType === 'ranking')
                     <div class="w-full py-2 px-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-xs font-bold">
                        Full ranking of all MDAs by weighted performance.
                    </div>
                @else
                    <div class="w-full py-2 px-4 bg-gray-100 rounded-lg text-gray-400 text-sm italic">
                        No additional filters required.
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Loading Indicator --}}
    <div wire:loading class="w-full text-center py-10">
        <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-white bg-green-600 transition ease-in-out duration-150">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Analyzing Financial Data...
        </div>
    </div>

    {{-- Main Results Area --}}
    <div wire:loading.remove>
        @if($reportType === 'executive')
            @include('livewire.admin.reports.executive_partial')

        @elseif($reportType === 'overview')
            @include('livewire.admin.reports.overview_partial')

        @elseif($reportType === 'detailed')
            @if($categoryId)
                @include('livewire.admin.reports.detailed_partial')
            @else
                <div class="bg-blue-50 border-l-4 border-blue-400 p-8 rounded-lg text-center">
                    <p class="text-blue-700 font-bold text-lg">Detailed Report Mode Active</p>
                    <p class="text-blue-600">Please select an Expenditure Category Group from the dropdown above.</p>
                </div>
            @endif

        {{-- UPDATED: CONSOLIDATED RANKING --}}
        @elseif($reportType === 'ranking')
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-green-600 flex justify-between items-center print:border-gray-400">
                    <div>
                        <h3 class="text-xl font-black text-gray-900 uppercase">State-Wide MDA Performance Ranking — Q{{ $quarter }}</h3>
                        <p class="text-sm text-gray-500 font-bold italic">Weighted Average: Revenue, Personnel, Overhead, & Capital Performance</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-bold text-gray-400 block uppercase">Total MDAs</span>
                        <span class="text-2xl font-black text-green-700">{{ count($results['full_list'] ?? []) }}</span>
                    </div>
                </div>

                <section>
                    {{-- Pass full_list to the dataset variable used in the partial --}}
                    @include('livewire.admin.reports.ranking_partial', ['dataset' => $results['full_list'] ?? []])
                </section>
            </div>
        @endif
    </div>

    <style>
        @media print {
            @page { size: landscape; margin: 1cm; }
            body { background-color: white !important; }
            .print\:hidden { display: none !important; }
            .shadow-sm, .shadow-md, .shadow-lg { box-shadow: none !important; border: 1px solid #ddd !important; }
            table { font-size: 8px !important; }
        }
    </style>
</div>