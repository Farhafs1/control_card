<div class="space-y-8 p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-end border-b border-slate-200 pb-6">
        <div>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Executive Fiscal Overview</h1>
            <p class="text-slate-500 font-medium mt-1">Fiscal Year {{ $siteSettings->fiscal_year ?? '2026' }} | Real-time Insights</p>
        </div>
        <div class="flex gap-3">
            <!-- <button class="bg-white border border-slate-200 px-6 py-2.5 rounded-xl text-sm font-bold text-slate-700 hover:border-emerald-500 transition-all shadow-sm">Briefing PDF</button>
            <button class="bg-slate-900 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg hover:bg-slate-800 transition-all">Export Analytics</button> -->
        </div>
    </div>

    <!-- Stats Section -->
    <div class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-indigo-50 p-6 rounded-3xl border border-indigo-100 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600 mb-2">Total Projected Revenue</p>
                <h3 class="text-2xl font-black text-indigo-900">₦{{ number_format($revenueTotal, 2) }}</h3>
            </div>
            <div class="bg-violet-50 p-6 rounded-3xl border border-violet-100 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-violet-600 mb-2">Actual Revenue Inflow</p>
                <h3 class="text-2xl font-black text-violet-600">₦{{ number_format($actualRevenue, 2) }}</h3>
            </div>
            <div class="bg-sky-50 p-6 rounded-3xl border border-sky-100 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-sky-600 mb-2">Inflow Rate</p>
                <h3 class="text-2xl font-black text-sky-600">{{ $inflow_rate }}</h3>
            </div>
            <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Remaining Projection</p>
                <h3 class="text-2xl font-black text-slate-900">₦{{ number_format($revenueBalance, 2) }}</h3>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Total Expenditure Budget</p>
                <h3 class="text-2xl font-black text-slate-900">₦{{ number_format($totalProvision, 2) }}</h3>
            </div>
            <div class="bg-emerald-50 p-6 rounded-3xl border border-emerald-100 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-600 mb-2">Total Released</p>
                <h3 class="text-2xl font-black text-emerald-600">₦{{ number_format($totalReleased, 2) }}</h3>
            </div>
            <div class="bg-blue-50 p-6 rounded-3xl border border-blue-100 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-600 mb-2">Budget Utilization</p>
                <h3 class="text-2xl font-black text-blue-600">{{ $utilization_percent }}</h3>
            </div>
            <div class="bg-rose-50 p-6 rounded-3xl border border-rose-100 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-rose-600 mb-2">Unspent Balance</p>
                <h3 class="text-2xl font-black text-rose-600">₦{{ number_format($variance_amount, 2) }}</h3>
            </div>
        </div>
    </div>

    <!-- Chart & Top MDAs Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        <!-- Chart -->
        <div class="lg:col-span-2 bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/20 h-full">
            <h3 class="text-lg font-bold text-slate-800 mb-6">Performance Trend</h3>
            <div class="h-80 w-full">
                <canvas id="fiscalChart"></canvas>
            </div>
        </div>

        <!-- Top MDAs -->
        <div class="bg-slate-900 text-white p-8 rounded-[2.5rem] shadow-2xl h-full">
            <h3 class="text-l font-black uppercase tracking-widest text-emerald-400 mb-6">Top Performing MDAs</h3>
            <div class="space-y-6">
                @forelse($topPerformingMdas as $mda)
                <div class="flex items-center justify-between border-b border-white/5 pb-4 last:border-0">
                    <div>
                        <p class="text-sm font-bold text-white">{{ $mda->name ?? 'N/A' }}</p>
                        <p class="text-sm text-slate-700">Code: {{ $mda->mda_code ?? 'N/A' }}</p>
                    </div>
                    <span class="text-sm font-black text-emerald-600">₦{{ number_format($mda->releases_sum_amount ?? 0, 0) }}</span>
                </div>
                @empty
                <p class="text-slate-500 text-sm italic">No performance data available.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm">
        <h3 class="text-lg font-bold text-slate-800 mb-6">Fiscal Burn Rate by Category</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-l font-black text-slate-700 uppercase tracking-widest border-b border-slate-100">
                        <th class="pb-4">Category</th>
                        <th class="pb-4">Provision</th>
                        <th class="pb-4">Released</th>
                        <th class="pb-4">Efficiency</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($fiscalPerformance as $label => $data)
                    <tr class="text-l font-medium text-slate-700">
                        <td class="py-5 capitalize">{{ $label }}</td>
                        <td class="py-5 font-mono">₦{{ number_format($data['budgeted'], 2) }}</td>
                        <td class="py-5 font-mono">₦{{ number_format($data['released'], 2) }}</td>
                        <td class="py-5">
                            @php $rate = $data['budgeted'] > 0 ? ($data['released'] / $data['budgeted']) * 100 : 0 @endphp
                            <div class="flex items-center gap-4">
                                <div class="w-32 bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="h-full {{ $rate > 80 ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ $rate }}%"></div>
                                </div>
                                <span class="text-sm font-black {{ $rate > 80 ? 'text-rose-600' : 'text-slate-700' }}">{{ number_format($rate, 1) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Replace 'DOMContentLoaded' with 'livewire:navigated'
        document.addEventListener('livewire:navigated', () => {
            const rawData = {!! $chartData !!};
            const labels = Object.keys(rawData);
            const budgeted = labels.map(key => rawData[key].budgeted);
            const released = labels.map(key => rawData[key].released);

            const ctx = document.getElementById('fiscalChart').getContext('2d');
            
            // IMPORTANT: Destroy existing chart if it exists to prevent memory leaks/glitches
            if (window.myFiscalChart instanceof Chart) {
                window.myFiscalChart.destroy();
            }

            window.myFiscalChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Budgeted', data: budgeted, backgroundColor: '#b90707' },
                        { label: 'Released', data: released, backgroundColor: '#038056' }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } }
                }
            });
        });
    </script>
</div>