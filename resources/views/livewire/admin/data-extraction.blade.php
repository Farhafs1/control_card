{{-- Modernized Extraction Hub Component --}}
{{-- Enhanced for v2 Scraper with Metrics Display and Date Controls --}}
<div class="space-y-10 p-8 max-w-7xl mx-auto" 
     @if($isCrawling) 
         wire:poll.5000ms="checkScrapeStatus" 
         wire:poll.visible="checkScrapeStatus"
     @endif>
    
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-slate-100 pb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="w-3 h-3 rounded-full {{ $isCrawling ? 'bg-amber-500 animate-ping' : 'bg-emerald-500' }}"></div>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.4em]">Automated Ledger Sync</span>
            </div>
            <h2 class="text-5xl font-black text-slate-900 tracking-tighter">Extraction Hub</h2>
        </div>
        
        @if($isCrawling)
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 px-4 py-2 bg-emerald-50 rounded-full">
                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                <span class="text-[10px] font-black text-emerald-700 uppercase tracking-wider">Live Extraction Active</span>
            </div>
        </div>
        @endif
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
                <span class="px-3 py-1 bg-slate-50 text-slate-500 text-[9px] font-black rounded-full uppercase tracking-tighter">Weekly Sync</span>
            </div>
        </div>
    </div>

    {{-- Main Control Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {{-- Parameter Panel --}}
        <div class="lg:col-span-{{ (!empty($consoleLogs) || $isCrawling) ? '4' : '12' }} transition-all duration-500">
            <div class="bg-white rounded-[3rem] border border-slate-100 shadow-2xl shadow-slate-200/60 p-10 relative overflow-hidden">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <i class="fas fa-spider text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-800 tracking-tight">Sync Controller</h3>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Define extraction parameters and run engine</p>
                    </div>
                </div>

                <div class="space-y-6">
                    @if(!$isCrawling)
                        {{-- Target Quantity --}}
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Target Quantity</label>
                            <select wire:model="batchLimit" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold text-slate-700 ring-1 ring-slate-100 focus:ring-2 focus:ring-emerald-500">
                                <option value="5">Test Sync (5 Releases)</option>
                                <option value="10">Light Sync (10 Releases)</option>
                                <option value="20">Medium Sync (20 Releases)</option>
                                <option value="50">Standard Sync (50 Releases)</option>
                                <option value="100">Deep Sync (100 Releases)</option>
                                <option value="200">Full Extraction (200 Releases)</option>
                                <option value="500">Maximum Extraction (500 Releases)</option>
                                <option value="5000">All Time Extraction (5000 Releases)</option>
                            </select>
                        </div>

                        {{-- Date Range Picker --}}
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Date Range (Optional)</label>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[8px] text-slate-400 ml-1">From</label>
                                    <input type="date" wire:model="fromDate" class="w-full bg-slate-50 border-none rounded-2xl p-3 text-m font-medium text-slate-700 ring-1 ring-slate-100 focus:ring-2 focus:ring-emerald-500">
                                </div>
                                <div>
                                    <label class="text-[8px] text-slate-400 ml-1">To</label>
                                    <input type="date" wire:model="toDate" class="w-full bg-slate-50 border-none rounded-2xl p-3 text-m font-medium text-slate-700 ring-1 ring-slate-100 focus:ring-2 focus:ring-emerald-500">
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 ml-1">Leave empty for automatic last 7 days</p>
                        </div>

                        {{-- Headless Mode Toggle --}}
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
                            <div>
                                <span class="text-sm font-bold text-slate-700">Background Mode</span>
                                <p class="text-[9px] text-slate-400">Run without visible browser window</p>
                            </div>
                            <button type="button" wire:click="$toggle('headlessMode')" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 {{ $headlessMode ? 'bg-emerald-600' : 'bg-slate-300' }}">
                                <span class="sr-only">Enable headless mode</span>
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $headlessMode ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="pt-4">
                        @if(!$isCrawling)
                            <button type="button" wire:click="startExtraction" wire:loading.attr="disabled" class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-black uppercase tracking-wider text-xs rounded-2xl shadow-xl shadow-emerald-200 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="startExtraction"><i class="fas fa-fire mr-2"></i> Fire Extraction Engine</span>
                                <span wire:loading wire:target="startExtraction"><i class="fas fa-spinner animate-spin mr-2"></i> Initializing...</span>
                            </button>
                        @else
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" wire:click="refreshStatus" class="py-4 bg-slate-600 hover:bg-slate-700 text-white font-black uppercase tracking-wider text-xs rounded-2xl transition-all">
                                    <i class="fas fa-sync-alt mr-2"></i> Refresh Status
                                </button>
                                <button type="button" wire:click="killEngine" class="py-4 bg-rose-600 hover:bg-rose-700 text-white font-black uppercase tracking-wider text-xs rounded-2xl shadow-xl shadow-rose-200 transition-all">
                                    <i class="fas fa-stop-circle mr-2"></i> Terminate Process
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Interactive Real-Time Console Window --}}
        @if(!empty($consoleLogs) || $isCrawling)
        <div class="lg:col-span-8 animate-in zoom-in-95 duration-300">
            <div class="bg-slate-950 rounded-[2.5rem] shadow-2xl shadow-slate-950/40 border border-slate-800/80 overflow-hidden flex flex-col h-[620px]">
                
                {{-- Console Top Bar --}}
                <div class="bg-slate-900/90 border-b border-slate-800/70 px-6 py-4 flex items-center justify-between backdrop-blur-md">
                    <div class="flex items-center gap-3">
                        <div class="flex gap-2 group">
                            @if(!$isCrawling)
                            <button type="button" wire:click="clearLogs" title="Clear Console" class="w-3 h-3 rounded-full bg-rose-500/90 hover:bg-rose-600 block transition-colors cursor-pointer relative flex items-center justify-center text-[7px] text-rose-950 font-bold">
                                <span class="opacity-0 group-hover:opacity-100 absolute">×</span>
                            </button>
                            @else
                            <span class="w-3 h-3 rounded-full bg-rose-500/40 block"></span>
                            @endif
                            <span class="w-3 h-3 rounded-full bg-amber-500/40 block"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-500/40 block"></span>
                        </div>
                        <span class="text-xs font-mono font-semibold text-slate-400 tracking-wider ml-2">engine_terminal@budget.com</span>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        @if($isCrawling)
                            <div class="text-[10px] font-mono text-emerald-400 font-bold bg-emerald-950/60 border border-emerald-500/30 px-2.5 py-1 rounded-md uppercase tracking-wider animate-pulse">
                                <i class="fas fa-cog fa-spin mr-1"></i> Running Process
                            </div>
                            <span class="text-sm font-mono font-bold text-slate-200 tracking-tight">{{ $consoleProgress }}%</span>
                        @else
                            <div class="flex items-center gap-2">
                                <div class="text-[10px] font-mono text-blue-400 font-bold bg-blue-950/60 border border-blue-500/30 px-2.5 py-1 rounded-md uppercase tracking-wider">
                                    <i class="fas fa-check-circle mr-1"></i> Process Halted
                                </div>
                                @if(!empty($consoleLogs))
                                <button type="button" wire:click="clearLogs" class="text-xs text-slate-400 hover:text-white font-mono bg-slate-800 hover:bg-slate-700 px-3 py-1 rounded-md transition-colors border border-slate-700">
                                    <i class="fas fa-trash-alt mr-1"></i> Clear
                                </button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Live Progress Track --}}
                <div class="bg-slate-900 h-[2px] w-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-400 transition-all duration-500 shadow-[0_0_8px_rgba(52,211,153,0.5)]" style="width: {{ $consoleProgress }}%"></div>
                </div>

                {{-- Real-time Metrics Display --}}
                @if($isCrawling && !empty($currentMetrics))
                <div class="bg-slate-900/50 border-b border-slate-800/70 px-6 py-3">
                    <div class="grid grid-cols-5 gap-3 text-center">
                        <div class="bg-slate-800/50 rounded-lg p-2">
                            <div class="flex items-center justify-center gap-1 text-[9px] text-slate-400 uppercase tracking-wider">
                                <i class="fas fa-tachometer-alt text-[8px]"></i>
                                <span>Processed</span>
                            </div>
                            <p class="text-base font-mono font-bold text-emerald-400">{{ number_format($currentMetrics['processed']) }}</p>
                        </div>
                        <div class="bg-slate-800/50 rounded-lg p-2">
                            <div class="flex items-center justify-center gap-1 text-[9px] text-slate-400 uppercase tracking-wider">
                                <i class="fas fa-plus-circle text-[8px]"></i>
                                <span>Created</span>
                            </div>
                            <p class="text-base font-mono font-bold text-blue-400">{{ number_format($currentMetrics['created']) }}</p>
                        </div>
                        <div class="bg-slate-800/50 rounded-lg p-2">
                            <div class="flex items-center justify-center gap-1 text-[9px] text-slate-400 uppercase tracking-wider">
                                <i class="fas fa-sync-alt text-[8px]"></i>
                                <span>Updated</span>
                            </div>
                            <p class="text-base font-mono font-bold text-amber-400">{{ number_format($currentMetrics['updated']) }}</p>
                        </div>
                        <div class="bg-slate-800/50 rounded-lg p-2">
                            <div class="flex items-center justify-center gap-1 text-[9px] text-slate-400 uppercase tracking-wider">
                                <i class="fas fa-forward text-[8px]"></i>
                                <span>Skipped</span>
                            </div>
                            <p class="text-base font-mono font-bold text-slate-400">{{ number_format($currentMetrics['skipped']) }}</p>
                        </div>
                        <div class="bg-slate-800/50 rounded-lg p-2">
                            <div class="flex items-center justify-center gap-1 text-[9px] text-slate-400 uppercase tracking-wider">
                                <i class="fas fa-exclamation-triangle text-[8px]"></i>
                                <span>Failed</span>
                            </div>
                            <p class="text-base font-mono font-bold text-rose-400">{{ number_format($currentMetrics['failed']) }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Interactive Log Display Output --}}
                <div id="liveConsoleLog" class="p-6 overflow-y-auto flex-1 font-mono text-[14px] text-slate-300 space-y-1.5 font-normal tracking-wide leading-relaxed scrollbar-thin scrollbar-thumb-slate-800 scrollbar-track-transparent selection:bg-emerald-500 selection:text-slate-950" style="font-family: 'JetBrains Mono', 'Fira Code', 'SF Mono', Monaco, Consolas, monospace;">
                    <div class="text-slate-500/90 font-medium text-[11px] tracking-wide mb-4 flex items-center gap-2 border-b border-slate-900 pb-3 select-none sticky top-0 bg-slate-950/95 backdrop-blur-sm py-2 -mt-2 z-10">
                        <i class="fas fa-terminal text-emerald-500/60"></i>
                        <span>// Initialized Session: {{ now()->toDateTimeString() }}</span>
                        @if($isCrawling)
                        <span class="ml-auto text-emerald-500/60 text-[9px]"><i class="fas fa-circle fa-pulse"></i> live stream</span>
                        @endif
                    </div>
                    
                    @forelse($consoleLogs as $log)
                        @php 
                            $cleanLog = trim(preg_replace('/\s+/', ' ', $log)); 
                        @endphp
                        
                        @if(empty($cleanLog) || $cleanLog === '---')
                            <div class="h-2"></div>
                        @else
                            <div class="flex items-start justify-start gap-2 group hover:bg-slate-900/40 px-2 py-1 rounded transition-colors w-full text-left">
                                <span class="text-emerald-600/80 font-bold select-none text-xs flex-shrink-0 mt-0.5">&gt;_</span>
                                <div class="flex-1 tracking-wide font-normal break-words">
                                    @if(str_contains(strtolower($cleanLog), '[error]') || str_contains(strtolower($cleanLog), 'failed') || str_contains(strtolower($cleanLog), 'critical'))
                                        <span class="text-rose-400 font-medium"><i class="fas fa-times-circle mr-1 text-[10px]"></i> {{ $cleanLog }}</span>
                                    @elseif(str_contains(strtolower($cleanLog), '[success]') || str_contains(strtolower($cleanLog), 'completed'))
                                        <span class="text-emerald-400 font-semibold"><i class="fas fa-check-circle mr-1 text-[10px]"></i> {{ $cleanLog }}</span>
                                    @elseif(str_contains(strtolower($cleanLog), '[warn]') || str_contains(strtolower($cleanLog), 'warning'))
                                        <span class="text-amber-400"><i class="fas fa-exclamation-triangle mr-1 text-[10px]"></i> {{ $cleanLog }}</span>
                                    @elseif(str_contains(strtolower($cleanLog), '[system]') || str_contains(strtolower($cleanLog), '[metrics]'))
                                        <span class="text-slate-400"><i class="fas fa-microchip mr-1 text-[10px]"></i> {{ $cleanLog }}</span>
                                    @elseif(str_contains(strtolower($cleanLog), '🔄') || str_contains($cleanLog, '✓'))
                                        <span class="text-cyan-400">{{ $cleanLog }}</span>
                                    @else
                                        <span class="text-slate-200">{{ $cleanLog }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="flex items-center justify-center py-12 text-slate-500 text-sm">
                            <i class="fas fa-terminal mr-2 text-emerald-500/40"></i>
                            <span>No console output. Start extraction to see logs.</span>
                        </div>
                    @endforelse
                </div>

                {{-- Console Footer --}}
                <div class="bg-slate-900/50 border-t border-slate-800/70 px-6 py-2 flex items-center justify-between text-[9px] font-mono text-slate-500">
                    <div class="flex items-center gap-3">
                        <span><i class="far fa-clock mr-1"></i> {{ now()->format('H:i:s') }}</span>
                        <span><i class="fas fa-database mr-1"></i> Staged: {{ number_format($stagedCount) }}</span>
                    </div>
                    @if($isCrawling)
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                        <span>Receiving telemetry</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Interactive auto-scroller for log flow --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Auto-scroll to bottom when new logs arrive
            @this.on('log-updated', () => {
                setTimeout(() => {
                    const consoleFrame = document.getElementById('liveConsoleLog');
                    if (consoleFrame) {
                        consoleFrame.scrollTop = consoleFrame.scrollHeight;
                    }
                }, 100);
            });
            
            // Refresh metrics periodically when active
            let metricsInterval = null;
            
            @this.on('scraper-started', () => {
                if (metricsInterval) clearInterval(metricsInterval);
                metricsInterval = setInterval(() => {
                    @this.call('refreshStatus');
                }, 5000);
            });
            
            @this.on('scraper-stopped', () => {
                if (metricsInterval) {
                    clearInterval(metricsInterval);
                    metricsInterval = null;
                }
            });
        });
    </script>
</div>