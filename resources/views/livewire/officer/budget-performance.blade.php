<div class="p-4 sm:p-6 lg:p-8 bg-gray-50 min-h-screen">
    {{-- Header Section --}}
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between print:hidden">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Portfolio Performance Analytics</h1>
            <p class="text-sm text-gray-600">Monitoring assigned MDAs and fiscal control.</p>
        </div>
        <div class="flex space-x-3">
            {{-- Print Button --}}
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition">
                Print
            </button>

            {{-- Export Excel Button --}}
            <button wire:click="export('excel')" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                Export Excel
            </button>

            {{-- Export PDF Button --}}
            <a href="{{ route('officer.export', ['format' => 'pdf', 'type' => 'detailed', 'q' => $quarter ?? 'all']) }}" 
            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition">
                Export PDF
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8 print:hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- 1. Quarter/Period Selection --}}
            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Select Fiscal Period</label>
                <select wire:model.live="quarter" 
                        @disabled($reportType === 'executive')
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500 font-bold {{ $reportType === 'executive' ? 'bg-gray-100 text-gray-400' : '' }}">
                    <option value="all">Cumulative (Full Year)</option>
                    <option value="1">1st Quarter (Jan - Mar)</option>
                    <option value="2">2nd Quarter (Apr - Jun)</option>
                    <option value="3">3rd Quarter (Jul - Sep)</option>
                    <option value="4">4th Quarter (Oct - Dec)</option>
                </select>
            </div>

            {{-- 2. Report Perspective --}}
            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Report Perspective</label>
                <select wire:model.live="reportType" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500 font-bold">
                    <option value="executive">Executive Overview (Annual Multi-Quarter)</option>
                    <option value="overview">Quarterly Summary (Single Quarter)</option>
                    <option value="detailed">Detailed Performance (By Subhead)</option>
                    <option value="ranking">Portfolio Ranking (My MDAs)</option>
                </select>
            </div>

            {{-- 3. Category Filter --}}
            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Expenditure Context</label>
                @if($reportType === 'detailed')
                    <select wire:model.live="categoryId" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500 font-bold bg-amber-50">
                        <option value="">-- Select Master Group --</option>
                        @foreach($this->masterCategories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @else
                    <div class="w-full py-2 px-4 bg-gray-100 rounded-lg text-gray-400 text-sm font-bold border border-gray-200">
                        Context Not Required
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Loading Indicator --}}
    <div wire:loading class="w-full text-center py-10">
        <div class="inline-flex items-center px-4 py-2 font-semibold text-sm shadow rounded-md text-white bg-green-600">
            Analyzing Portfolio Data...
        </div>
    </div>

    {{-- Main Results Area --}}
    <div wire:loading.remove>
        @if($reportType === 'executive')
            @include('livewire.officer.reports.executive_partial')

        @elseif($reportType === 'overview')
            @include('livewire.officer.reports.overview_partial')

        @elseif($reportType === 'detailed')
            @if($categoryId)
                @include('livewire.officer.reports.detailed_partial')
            @else
                <div class="bg-blue-50 border-l-4 border-blue-400 p-8 rounded-lg text-center">
                    <p class="text-blue-700 font-bold">Please select an Expenditure Category Group.</p>
                </div>
            @endif

        @elseif($reportType === 'ranking')
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-green-600 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-black text-gray-900 uppercase">Assigned Portfolio Ranking — Q{{ $quarter }}</h3>
                        <p class="text-sm text-gray-500 font-bold italic">Performance restricted to your assigned MDAs</p>
                    </div>
                </div>
                <section>
                    {{-- Note: Passed $dataset directly as requested by the Service return structure --}}
                    @include('livewire.officer.reports.ranking_partial', ['dataset' => $dataset])
                </section>
            </div>
        @endif
    </div>
</div>