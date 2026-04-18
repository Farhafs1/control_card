<div class="space-y-10 p-8 max-w-7xl mx-auto">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-slate-100 pb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="w-3 h-3 rounded-full {{ $isCrawling ? 'bg-amber-500 animate-ping' : 'bg-emerald-500' }}"></div>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.4em]">Automated Ledger Sync</span>
            </div>
            <h2 class="text-5xl font-black text-slate-900 tracking-tighter">Extraction Hub</h2>
        </div>
        
        <div class="flex items-center gap-4">
            <button wire:click="$toggle('showSettings')" class="px-5 py-2.5 rounded-xl bg-white border border-slate-200 text-[10px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm">
                <i class="fas {{ $showSettings ? 'fa-times' : 'fa-cog text-emerald-500' }}"></i> 
                {{ $showSettings ? 'Hide' : 'Configure' }} Connection
            </button>
        </div>
    </div>

    {{-- Overview Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('admin.staged-releases') }}" class="relative overflow-hidden group p-8 bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/50 hover:border-emerald-500 transition-all duration-500">
            <div class="relative z-10 flex flex-col h-full">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Awaiting Approval</p>
                <h3 class="text-5xl font-black text-slate-900 group-hover:text-emerald-600 transition-colors">{{ number_format($stagedCount) }}</h3>
                <p class="text-[10px] text-emerald-600 font-bold uppercase mt-6 flex items-center">
                    Enter Staging Area <i class="fas fa-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
                </p>
            </div>
            <i class="fas fa-layer-group absolute -bottom-4 -right-4 text-8xl text-slate-50 group-hover:text-emerald-50 transition-colors"></i>
        </a>

        <div class="p-8 bg-slate-900 rounded-[2.5rem] text-white shadow-xl">
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Ledger Integrity</p>
            <h3 class="text-4xl font-black">{{ number_format($totalPermanent) }}</h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Total Committed Records</p>
            <div class="mt-8 h-1 w-full bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500" style="width: 100%"></div>
            </div>
            <p class="text-[9px] text-slate-500 mt-2 font-bold uppercase">System Status: Optimal</p>
        </div>

        <div class="p-8 bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/50">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Last Handshake</p>
            <h3 class="text-2xl font-black text-slate-800">{{ $lastSync }}</h3>
            <div class="mt-6 flex gap-2">
                <span class="px-3 py-1 bg-blue-50 text-blue-600 text-[9px] font-black rounded-full uppercase tracking-tighter">Automatic Fetch</span>
                <span class="px-3 py-1 bg-slate-50 text-slate-500 text-[9px] font-black rounded-full uppercase tracking-tighter">Daily Sync</span>
            </div>
        </div>
    </div>

    {{-- Main Engine Control --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {{-- Settings Panel --}}
        <div class="lg:col-span-{{ $showSettings ? '8' : '12' }} transition-all duration-500">
            <div class="bg-white rounded-[3rem] border border-slate-100 shadow-2xl shadow-slate-200/60 p-10 relative overflow-hidden">
                @if($isCrawling)
                    <div class="absolute inset-0 bg-white/90 backdrop-blur-md z-20 flex flex-col items-center justify-center">
                        <div class="w-16 h-16 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                        <h4 class="mt-6 text-xl font-black text-slate-900 tracking-tighter">Engine is Deep Crawling</h4>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-2">Communicating with external portal API...</p>
                    </div>
                @endif

                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <i class="fas fa-spider text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-800 tracking-tight">Sync Controller</h3>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Define your extraction parameters</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Begin Date</label>
                        <input type="date" wire:model="startDate" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold text-slate-700 ring-1 ring-slate-100 focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">End Date</label>
                        <input type="date" wire:model="endDate" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold text-slate-700 ring-1 ring-slate-100 focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Capture Depth</label>
                        <select wire:model="batchLimit" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold text-slate-700 ring-1 ring-slate-100 focus:ring-2 focus:ring-emerald-500">
                            <option value="10">Light Sync (10)</option>
                            <option value="50">Standard Sync (50)</option>
                            <option value="100">Deep Sync (100)</option>
                            <option value="200">Full Extraction (200)</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button id="syncBtn" class="btn btn-primary">Sync Records</button>

                        <div id="progressContainer" style="display:none; margin-top: 20px;">
                            <div class="progress">
                                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                    role="progressbar" style="width: 0%">0%</div>
                            </div>
                            <p id="statusText" class="text-muted mt-2">Initializing...</p>
                        </div>
                    </div>
                </div>

                {{-- Expandable Advanced Settings --}}
                @if($showSettings)
                <div class="mt-12 pt-12 border-t border-slate-50 grid grid-cols-1 md:grid-cols-3 gap-8 animate-in fade-in slide-in-from-top-4">
                    <div class="space-y-3 md:col-span-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Target URL</label>
                        <input type="text" wire:model="scraperUrl" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-medium">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Username</label>
                        <input type="text" wire:model="scraperUser" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-medium">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Password</label>
                        <input type="password" wire:model="scraperPass" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-medium">
                    </div>
                    <div class="md:col-span-3 flex justify-end">
                        <button wire:click="saveSettings" class="text-[10px] font-black text-emerald-600 uppercase tracking-widest border-b-2 border-emerald-100 pb-1 hover:border-emerald-500 transition-all">
                            Apply & Save Credentials
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <script>
        document.getElementById('syncBtn').addEventListener('click', function() {
            // 1. Grab the limit from the select element
            const limit = document.querySelector('select[wire\\:model="batchLimit"]').value;
            
            this.disabled = true;
            document.getElementById('progressContainer').style.display = 'block';

            // 2. Pass the limit as a query parameter
            fetch(`/sync-records?limit=${limit}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                const interval = setInterval(() => {
                    fetch('/sync-progress')
                        .then(res => res.json())
                        .then(data => {
                            document.getElementById('progressBar').style.width = data.percent + '%';
                            document.getElementById('progressBar').innerText = data.percent + '%';
                            document.getElementById('statusText').innerText = data.status;

                            if (data.percent >= 100) {
                                clearInterval(interval);
                                document.getElementById('statusText').innerText = "Sync Complete!";
                                this.disabled = false;
                            }
                        });
                }, 2000);
            });
        });
        </script>



</div>