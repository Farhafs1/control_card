<div class="p-6 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        
        @if(!$showInsight)
            {{-- VIEW 1: DATA & FILTERS --}}
            
            {{-- Header --}}
            <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">Executive Release Analytics</h1>
                    <p class="text-slate-500 text-sm font-medium">Strategic oversight and narrative auditing of state expenditure.</p>
                </div>
                
                {{-- Action: Trigger AI Insight --}}
                <div class="flex items-center gap-3">
                    <button wire:click="generateAIReport" 
                            wire:loading.remove 
                            wire:target="generateAIReport"
                            class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-bold flex items-center gap-3 shadow-xl shadow-emerald-100 transition-all group">
                        <i class="fas fa-brain group-hover:scale-125 transition-transform"></i> 
                        Generate AI Insight
                    </button>

                    <button disabled 
                            wire:loading 
                            wire:target="generateAIReport"
                            class="px-6 py-3 bg-slate-200 text-slate-500 rounded-2xl font-bold flex items-center gap-3 cursor-not-allowed border border-slate-300">
                        <i class="fas fa-spinner animate-spin"></i> 
                        Analyzing Data...
                    </button>
                </div>
            </div>

            {{-- Advanced Filter Bar --}}
            <div class="bg-white p-2 rounded-2xl shadow-sm border border-slate-200 mb-6 flex flex-wrap gap-2 items-center">
                <div class="flex-1 min-w-[300px] relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i class="fas fa-search text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                    </div>
                    <input type="text" 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Search narrative, audit codes or references..." 
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border-transparent rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all placeholder:text-slate-400">
                </div>

                <div class="flex items-center gap-2">
                    <!-- Quarter Select -->
                    <div class="relative">
                        <select wire:model.live="quarter" class="appearance-none pl-9 pr-8 py-2.5 bg-slate-50 border-transparent rounded-xl text-sm font-medium text-slate-700 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer">
                            <option value="">Full Year</option>
                            <option value="1">Q1: Jan - Mar</option>
                            <option value="2">Q2: Apr - Jun</option>
                            <option value="3">Q3: Jul - Sep</option>
                            <option value="4">Q4: Oct - Dec</option>
                        </select>
                        <i class="fas fa-calendar-alt absolute left-3 top-3.5 text-slate-400 text-xs pointer-events-none"></i>
                    </div>

                    <!-- Category Select -->
                    <div class="relative">
                        <select wire:model.live="categoryId" class="appearance-none pl-9 pr-8 py-2.5 bg-slate-50 border-transparent rounded-xl text-sm font-medium text-slate-700 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer">
                            <option value="">All Categories</option>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <i class="fas fa-layer-group absolute left-3 top-3.5 text-slate-400 text-xs pointer-events-none"></i>
                    </div>

                    <button wire:click="resetFilters" 
                            class="px-4 py-2.5 text-xs font-bold text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all uppercase tracking-wider">
                        <i class="fas fa-undo-alt mr-1.5"></i> Reset
                    </button>
                </div>
            </div>

            {{-- Stats Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-4 mb-8">
                <div class="md:col-span-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between hover:border-emerald-200 transition-colors">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Value</span>
                    <span class="text-2xl font-black text-emerald-600 truncate">₦{{ number_format($stats['total_value'], 2) }}</span>
                </div>

                <div class="md:col-span-2 bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between hover:border-blue-200 transition-colors">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Releases</span>
                    <span class="text-2xl font-black text-blue-600">{{ $stats['count'] }}</span>
                </div>

                <div class="md:col-span-3 bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between hover:border-slate-300 transition-colors">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Average</span>
                    <span class="text-2xl font-black text-slate-700 truncate">₦{{ number_format($stats['avg_release'], 2) }}</span>
                </div>

                <div class="md:col-span-3 bg-emerald-900 p-5 rounded-2xl shadow-sm border border-emerald-800 flex flex-col justify-between">
                    <span class="text-[10px] font-bold text-emerald-300/60 uppercase tracking-widest">Largest</span>
                    <span class="text-xl font-black text-white truncate">₦{{ number_format($stats['max_release'], 2) }}</span>
                </div>
            </div>

            {{-- Charts Row --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 text-center">Budget Burn Rate</h3>

                    <!-- The relative container must have a defined height -->
                    <div class="relative h-48 w-full flex items-center justify-center">
                        
                        <!-- 1. The Chart (Ignored by Livewire so it stays stable) -->
                        <div wire:ignore class="absolute inset-0">
                            <canvas id="burnRateChart"></canvas>
                        </div>

                        <!-- 2. The Text (Monitored by Livewire so it updates) -->
                        <!-- We remove 'absolute inset-0' and 'mt-8' to prevent the overlap shift -->
                        <div class="relative z-10 text-center pointer-events-none">
                            <span 
                                wire:key="burn-val-{{ $burnRate }}" 
                                id="burnRateText" 
                                class="text-3xl font-black text-slate-800 transform translate-y-8"
                            >
                                {{ $burnRate }}%
                            </span>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 bg-white p-6 rounded-3xl shadow-sm border border-slate-100" wire:ignore>
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest">Release Velocity</h3>
                            <p class="text-[11px] text-slate-400">Quarterly performance analysis</p>
                        </div>
                    </div>
                    <div class="h-52 relative">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Narrative Table --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Release Info</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">MDA & Category</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Audit Narration</th>
                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($releases as $release)
                            <tr class="hover:bg-slate-50 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="block text-sm font-bold text-slate-700">{{ \Carbon\Carbon::parse($release->release_date)->format('d M, Y') }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $release->reference_no }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="block text-sm text-slate-700 font-bold leading-tight">{{ $release->mda->name ?? 'N/A' }}</span>
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[9px] bg-slate-100 text-slate-600 font-black uppercase tracking-tighter">
                                        {{ $release->subhead->category->type ?? 'General' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-xs text-slate-500 italic line-clamp-2 max-w-md">"{{ $release->narration }}"</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-sm font-black {{ $release->amount >= 100000000 ? 'text-rose-600' : 'text-slate-900' }}">
                                        ₦{{ number_format($release->amount, 2) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-16 text-center text-slate-400 italic">No matching records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4 bg-slate-50 border-t border-slate-100">
                    {{ $releases->links() }}
                </div>
            </div>

        @else
            {{-- VIEW 2: AI INSIGHT PAGE --}}
            <div class="max-w-5xl mx-auto animate-in fade-in slide-in-from-bottom-4 duration-700">
                <div class="mb-8 flex items-center justify-between">
                    <button wire:click="closeInsight" class="group flex items-center gap-2 text-slate-500 hover:text-slate-900">
                        <div class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center">
                            <i class="fas fa-arrow-left text-xs"></i>
                        </div>
                        <span class="text-xs font-bold uppercase tracking-widest">Back to Data</span>
                    </button>

                    <!-- <div class="flex items-center gap-3">
                        <button wire:click="exportReport('pdf')" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-rose-100 transition-all flex items-center gap-2">
                            <i class="fas fa-file-pdf"></i> Save PDF
                        </button>
                    </div> -->
                </div>

                <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
                    <div class="bg-slate-900 p-8 text-white relative">
                        <div class="relative z-10">
                            <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-full text-[10px] font-black uppercase tracking-widest border border-emerald-500/30">
                                AI Intelligence Report
                            </span>
                            <h2 class="text-3xl font-black mt-4 tracking-tight">Expenditure Insight Audit</h2>
                            <p class="text-slate-400 text-sm mt-2">Katsina State Financial Management Data</p>
                        </div>
                    </div>

                    <div class="p-10">
                        @if($isAnalyzing)
                            <div class="flex flex-col items-center py-20 text-center">
                                <div class="w-16 h-16 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                                <h3 class="mt-8 font-black text-slate-900 text-lg">Synthesizing Financial Data...</h3>
                                <p class="text-slate-400 text-sm mt-2">Auditing narrative patterns...</p>
                            </div>
                        @else
                            <div class="audit-content text-slate-700 leading-relaxed space-y-6 text-lg">
                                @php
                                    $formattedAI = preg_replace('/###\s+(.*)/', '<h3 class="text-xl font-black text-emerald-700 mt-10 mb-4 tracking-tight uppercase border-b-2 border-slate-50 pb-2">$1</h3>', $aiAnalysis);
                                    $formattedAI = preg_replace('/\*\*(.*?)\*\*/', '<strong class="text-slate-900 font-bold">$1</strong>', $formattedAI);
                                @endphp
                                {!! nl2br($formattedAI) !!}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Chart Scripts --}}
        <script>
            function renderExecutiveCharts(liveData = null) {
                // 1. Identify all canvas elements
                const canvases = {
                    sector: document.getElementById('sectorChart'),
                    mda: document.getElementById('mdaChart'),
                    burn: document.getElementById('burnRateChart'),
                    trend: document.getElementById('trendChart'),
                    status: document.getElementById('statusChart')
                };

                // 2. Clear existing chart instances to prevent overlapping
                Object.values(canvases).forEach(canvas => {
                    if (!canvas) return;
                    const existing = Chart.getChart(canvas);
                    if (existing) existing.destroy();
                });

                // 3. Define Data Sources
                let sectors, mdas, burnRate, trends, status;

                if (liveData) {
                    sectors = liveData.sectors;
                    mdas = liveData.mdas;
                    burnRate = liveData.burnRate;
                    trends = liveData.trends;
                    status = liveData.status;
                } else {
                    sectors = {
                        // Adding ?? [] ensures that if the variable is missing, it just returns an empty array instead of a 500 error
                        labels: @json(isset($sectorChartData) ? $sectorChartData->pluck('label')->toArray() : []),
                        values: @json(isset($sectorChartData) ? $sectorChartData->pluck('total')->toArray() : [])
                    };
                    mdas = {
                        labels: @json($mdaChartData->pluck('label')->toArray()),
                        values: @json($mdaChartData->pluck('total')->toArray())
                    };
                    burnRate = {{ $burnRate ?? 0 }};
                    trends = {
                        labels: @json($trendLabels ?? []),
                        values: @json($trendValues ?? [])
                    };
                    status = {
                        labels: @json($statusData->pluck('status')->toArray()),
                        values: @json($statusData->pluck('count')->toArray())
                    };
                }

                // --- CHART RENDERING LOGIC ---

                // 1. Sector Chart (Doughnut)
                if (canvases.sector) {
                    new Chart(canvases.sector, {
                        type: 'doughnut',
                        data: {
                            labels: sectors.labels,
                            datasets: [{
                                data: sectors.values,
                                backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#6366f1', '#94a3b8'],
                                borderWidth: 2,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: { maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom' } } }
                    });
                }

                // 2. MDA Chart (Bar)
                if (canvases.mda) {
                    new Chart(canvases.mda, {
                        type: 'bar',
                        data: {
                            labels: mdas.labels,
                            datasets: [{ label: 'Total (₦)', data: mdas.values, backgroundColor: '#0f172a', borderRadius: 6 }]
                        },
                        options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
                    });
                }

                // 3. Burn Rate Chart (Gauge)
                if (canvases.burn) {
                    new Chart(canvases.burn, {
                        type: 'doughnut',
                        data: {
                            datasets: [{
                                data: [burnRate, 100 - burnRate],
                                backgroundColor: ['#10b981', '#f1f5f9'],
                                circumference: 180,
                                rotation: 270,
                                borderWidth: 0,
                                cutout: '80%'
                            }]
                        },
                        options: { maintainAspectRatio: false, plugins: { tooltip: { enabled: false }, legend: { display: false } } }
                    });
                }

                // 4. Release Velocity Chart (Area/Line)
                if (canvases.trend) {
                    new Chart(canvases.trend, {
                        type: 'line',
                        data: {
                            labels: trends.labels,
                            datasets: [{
                                fill: true,
                                label: 'Monthly Release',
                                data: trends.values,
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.4,
                                pointRadius: 2
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, ticks: { display: false }, grid: { display: false } },
                                x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                            },
                            plugins: { legend: { display: false } }
                        }
                    });
                }

                // 5. Project Status Chart (Polar Area)
                if (canvases.status) {
                    new Chart(canvases.status, {
                        type: 'polarArea',
                        data: {
                            labels: status.labels,
                            datasets: [{
                                data: status.values,
                                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444']
                            }]
                        },
                        options: { maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
                    });
                }
            }

            // Event Listeners
            document.addEventListener('livewire:navigated', () => renderExecutiveCharts());
            window.addEventListener('load', () => renderExecutiveCharts());
            
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('chartUpdated', (eventData) => {
                    const data = Array.isArray(eventData) ? eventData[0] : eventData;
                    renderExecutiveCharts(data);
                });
            });
        </script>
</div>