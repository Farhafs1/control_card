<div class="p-6 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto">
        
        @if(!$showInsight)
            {{-- VIEW 1: DATA & FILTERS (Current View) --}}
            
            {{-- Header --}}
            <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">Executive Release Analytics</h1>
                    <p class="text-slate-500 text-sm font-medium">Strategic oversight and narrative auditing of state expenditure.</p>
                </div>
                
                {{-- Action: Trigger AI Insight --}}
                <div class="flex items-center gap-3">
                    {{-- BUTTON: Visible when NOT loading --}}
                    <button wire:click="generateAIReport" 
                            wire:loading.remove 
                            wire:target="generateAIReport"
                            class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-bold flex items-center gap-3 shadow-xl shadow-emerald-100 transition-all group">
                        <i class="fas fa-brain group-hover:scale-125 transition-transform"></i> 
                        Generate AI Insight
                    </button>

                    {{-- BUTTON: Visible ONLY while loading --}}
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
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 mb-6 flex flex-wrap gap-4 items-center">
                <div class="flex-1 min-w-[250px] relative">
                    <i class="fas fa-search absolute left-3 top-3.5 text-slate-300"></i>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search narrative audit or ref..." 
                           class="w-full pl-10 rounded-xl border-slate-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <select wire:model.live="categoryId" class="rounded-xl border-slate-200 text-sm focus:ring-emerald-500">
                    <option value="">All State Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->type }}</option>
                    @endforeach
                </select>

                <select wire:model.live="minAmount" class="rounded-xl border-slate-200 text-sm focus:ring-emerald-500">
                    <option value="">Any Amount</option>
                    <option value="1000000">₦1 Million +</option>
                    <option value="100000000">₦100 Million +</option>
                    <option value="1000000000">₦1 Billion +</option>
                </select>

                <button wire:click="$set('search', '')" class="px-4 py-2 text-xs font-bold text-slate-400 hover:text-rose-600 uppercase tracking-tighter">
                    Reset
                </button>
            </div>

            {{-- Top Level Stats --}}
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-8">
                {{-- Total Value: Large (4 units) --}}
                <div class="md:col-span-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between hover:border-emerald-200 transition-colors">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Value</span>
                    <span class="text-1.5xl font-black text-emerald-600 truncate">₦{{ number_format($stats['total_value'], 2) }}</span>
                </div>

                {{-- Release Count: Small (2 units) --}}
                <div class="md:col-span-2 bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between hover:border-blue-200 transition-colors">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Release Count</span>
                    <span class="text-1.5xl font-black text-blue-600">{{ $stats['count'] }}</span>
                </div>

                {{-- Average Release: Medium (3 units) --}}
                <div class="md:col-span-3 bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between hover:border-slate-300 transition-colors">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Average Release</span>
                    <span class="text-1.5xl font-black text-slate-700 truncate">₦{{ number_format($stats['avg_release'], 2) }}</span>
                </div>

                {{-- Largest Release: Medium (3 units) --}}
                <div class="md:col-span-3 bg-emerald-900 p-5 rounded-2xl shadow-sm border border-emerald-800 flex flex-col justify-between">
                    <span class="text-[10px] font-bold text-emerald-300/60 uppercase tracking-widest">Largest</span>
                    <span class="text-1.5xl font-black text-white truncate">₦{{ number_format($stats['max_release'], 2) }}</span>
                </div>
            </div>

           {{-- Analytical Charts --}}
           {{-- Row 1: High-Level Pulse --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                {{-- 1. Budget Performance (Burn Rate) --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col items-center justify-center text-center" wire:ignore>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Budget Burn Rate</h3>
                    <div class="relative h-48 w-full">
                        <canvas id="burnRateChart"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center mt-8">
                            <span class="text-3xl font-black text-slate-800">{{ $burnRate }}%</span>
                            <span class="text-[10px] text-slate-400 uppercase font-semibold">of Total Budget</span>
                        </div>
                    </div>
                </div>

                {{-- 2. Release Trends (Monthly Velocity) --}}
                    <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-slate-200" wire:ignore>
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Release Velocity</h3>
                                <p class="text-[10px] text-slate-400 mt-1">Monthly spending trend for the current year</p>
                            </div>
                            <div class="bg-blue-50 text-blue-600 px-3 py-1 rounded-full text-[10px] font-bold">
                                JAN - DEC 2026
                            </div>
                        </div>
                        <div class="h-48">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
            </div>

            {{-- Row 2: Composition & Rankings --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    {{-- 3. Top 10 Spending MDAs --}}
                    <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-slate-200" wire:ignore>
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Top 10 Spending MDAs</h3>
                                <p class="text-[10px] text-slate-400 mt-1">Highest expenditure departments</p>
                            </div>
                            <i class="fas fa-university text-blue-500 bg-blue-50 p-2 rounded-lg"></i>
                        </div>
                        <div class="h-64">
                            <canvas id="mdaChart"></canvas>
                        </div>
                    </div>
            
            </div>
            
            {{-- Row 3: Composition & Lifecycle --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">    
                {{-- 4. Expenditure by Type (Personnel vs Overhead vs Capital) --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200" wire:ignore>
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Expenditure Composition</h3>
                            <p class="text-[10px] text-slate-400 mt-1">Ratio of Recurrent to Capital spending</p>
                        </div>
                        <i class="fas fa-chart-pie text-emerald-500 bg-emerald-50 p-2 rounded-lg"></i>
                    </div>
                    <div class="h-72"> {{-- Standardized height --}}
                        <canvas id="sectorChart"></canvas>
                    </div>
                </div>

                {{-- 5. Project Implementation Status --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200" wire:ignore>
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Project Completion Lifecycle</h3>
                            <p class="text-[10px] text-slate-400 mt-1">Distribution of projects by current status</p>
                        </div>
                        <i class="fas fa-tasks text-orange-500 bg-orange-50 p-2 rounded-lg"></i>
                    </div>
                    <div class="h-72"> {{-- Standardized height --}}
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
            
            {{-- Narrative Audit Table --}}
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
                                <td class="px-6 py-4">
                                    <span class="block text-sm font-bold text-slate-700">{{ \Carbon\Carbon::parse($release->release_date)->format('d M, Y') }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono group-hover:text-emerald-500 transition-colors">{{ $release->reference_no }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="block text-sm text-slate-700 font-bold leading-tight">{{ $release->mda->name ?? 'N/A' }}</span>
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[9px] bg-slate-100 text-slate-600 font-black uppercase tracking-tighter">
                                        {{ $release->subhead->category->type ?? 'General' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-xs text-slate-500 italic max-w-md leading-relaxed border-l-2 border-slate-100 pl-3">
                                        "{{ $release->narration }}"
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-sm font-black {{ $release->amount >= 1000000000 ? 'text-rose-600' : 'text-slate-900' }}">
                                        ₦{{ number_format($release->amount, 2) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-folder-open text-slate-200 text-5xl mb-4"></i>
                                        <p class="text-slate-400 text-sm italic font-medium">No releases found matching current filters.</p>
                                    </div>
                                </td>
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
                
                {{-- Insight Header --}}
                <div class="mb-8 flex items-center justify-between">
                    <button wire:click="closeInsight" class="group flex items-center gap-2 text-slate-500 hover:text-slate-900 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center group-hover:bg-slate-100">
                            <i class="fas fa-arrow-left text-xs"></i>
                        </div>
                        <span class="text-xs font-bold uppercase tracking-widest">Back to Data</span>
                    </button>

                    <div class="flex items-center gap-3">
                        <button wire:click="exportReport('pdf')" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-rose-700 shadow-lg shadow-rose-100 transition-all flex items-center gap-2">
                            <i class="fas fa-file-pdf"></i> Save as PDF
                        </button>
                        <button wire:click="exportReport('ppt')" class="px-4 py-2 bg-orange-500 text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-orange-600 shadow-lg shadow-orange-100 transition-all flex items-center gap-2">
                            <i class="fas fa-file-powerpoint"></i> PPT Briefing
                        </button>
                    </div>
                </div>

                {{-- AI Content Card --}}
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
                    <div class="bg-slate-900 p-8 text-white relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10">
                            <i class="fas fa-brain text-9xl"></i>
                        </div>

                        <div class="relative z-10">
                            <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-full text-[10px] font-black uppercase tracking-widest border border-emerald-500/30">
                                AI Intelligence Report
                            </span>
                            <h2 class="text-3xl font-black mt-4 tracking-tight">Katsina State Expenditure Audit</h2>
                            <p class="text-slate-400 text-sm mt-2 font-medium">Reflecting filtered dataset: {{ $stats['count'] }} transactions totaling ₦{{ number_format($stats['total_value'], 2) }}</p>
                        </div>
                    </div>

                    <div class="p-10">
                        @if($isAnalyzing)
                            <div class="flex flex-col items-center py-20">
                                <div class="relative">
                                    <div class="w-16 h-16 border-4 border-emerald-100 rounded-full"></div>
                                    <div class="w-16 h-16 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin absolute top-0 left-0"></div>
                                </div>
                                <h3 class="mt-8 font-black text-slate-900 text-lg">Synthesizing Financial Data...</h3>
                                <p class="text-slate-400 text-sm mt-2 animate-pulse">Gemini 2.5 is auditing narrative patterns for Katsina State.</p>
                            </div>
                        @else
                            <div class="max-w-none">
                                <div class="text-slate-700 leading-relaxed space-y-6 text-lg">
                                    @php
                                        // 1. Convert ### Headlines into Styled HTML Headers
                                        $formattedAI = preg_replace(
                                            '/###\s+(.*)/', 
                                            '<h3 class="text-xl font-black text-emerald-700 mt-10 mb-4 tracking-tight uppercase border-b-2 border-slate-50 pb-2">$1</h3>', 
                                            $aiAnalysis
                                        );

                                        // 2. Convert **Bold** to strong with primary slate color
                                        $formattedAI = preg_replace(
                                            '/\*\*(.*?)\*\*/', 
                                            '<strong class="text-slate-900 font-bold">$1</strong>', 
                                            $formattedAI
                                        );
                                    @endphp

                                    <div class="audit-content">
                                        {!! nl2br($formattedAI) !!}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-12 pt-8 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400">
                                        <i class="fas fa-shield-alt text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Document Security</p>
                                        <p class="text-xs text-slate-500 italic max-w-xs leading-tight">
                                            This insight was generated using audited state release records. Internal use only for the Katsina State Government.
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 px-4 py-2 bg-emerald-50 rounded-xl border border-emerald-100">
                                    <i class="fas fa-check-circle text-emerald-500"></i>
                                    <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-widest">Data Verified</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <style>
                    .audit-content {
                        font-family: 'Inter', system-ui, sans-serif;
                    }
                    /* Customizing the strong tags within the AI output */
                    .audit-content strong {
                        color: #0f172a;
                    }
                    /* Ensure list-like spacing for headers */
                    .audit-content h3:first-child {
                        margin-top: 0;
                    }
                </style>
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
                        labels: @json($sectorChartData->pluck('label')->toArray()),
                        values: @json($sectorChartData->pluck('total')->toArray())
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