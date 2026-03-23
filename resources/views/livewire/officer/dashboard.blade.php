<div>
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-800">Welcome, {{ auth()->user()->name }}</h2>
        <p class="text-slate-500 font-medium">
            Managing: <span class="text-blue-600 font-bold">{{ $mda->name ?? 'No MDA Assigned Yet' }}</span>
        </p>
    </div>

    @if(!$mda)
        <div class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-6">
            <p class="text-amber-700 font-medium">Attention: You have not been assigned to an MDA. Please contact the Admin.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Allocation</p>
                <h3 class="text-2xl font-bold text-slate-900">₦ 0.00</h3>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Releases</p>
                <h3 class="text-2xl font-bold text-emerald-600">₦ 0.00</h3>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Balance</p>
                <h3 class="text-2xl font-bold text-blue-600">₦ 0.00</h3>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200">
            <div class="p-6 border-b border-slate-100">
                <h4 class="font-bold text-slate-800">Recent Releases</h4>
            </div>
            <div class="p-12 text-center text-slate-400 italic">
                No budget data uploaded for {{ $mda->name }} yet.
            </div>
        </div>
    @endif
</div>