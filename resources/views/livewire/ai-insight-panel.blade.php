<div class="mt-8 overflow-hidden bg-white border-[6px] border-[#BDB76B] rounded-2xl shadow-sm">
    <!-- Header: High Contrast & Professional -->
    <div class="bg-white border-b border-slate-100 px-8 py-10">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-3xl font-black text-slate-900 tracking-tight mb-2">
                    Strategic Intelligence Report
                </h3>
                <div class="flex items-center gap-2">
                    <span class="h-1 w-8 bg-[#BDB76B]"></span>
                    <p class="text-xs font-black text-[#BDB76B] uppercase tracking-[0.2em]">
                        Executive Fiscal Briefing
                    </p>
                </div>
            </div>
            
            @if($loading)
                <div class="flex items-center gap-3 px-4 py-2 border border-slate-200 rounded-full bg-slate-50">
                    <div class="h-3 w-3 rounded-full bg-[#BDB76B] animate-ping"></div>
                    <span class="text-[10px] font-black text-slate-600 uppercase tracking-tighter">Processing Ledger...</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Content Area: Maximum Readability -->
    <div class="px-8 py-14 lg:px-20 bg-white">
        @if($insight)
            {{-- 
                The 'prose' class is modified here to force spacing and weight.
                We use 'prose-h3' as that is the standard markdown header level.
            --}}
           <style>
                /* Direct overrides for the AI Insight content */
                .insight-content h1, .insight-content h2, .insight-content h3 {
                    font-weight: 900 !important;
                    color: #0f172a !important; /* slate-900 */
                    display: block !important;
                    margin-top: 2.5rem !important;
                    margin-bottom: 1.5rem !important;
                    line-height: 1.2 !important;
                }
                .insight-content h1 { font-size: 2.25rem !important; }
                .insight-content h2 { font-size: 1.875rem !important; }
                .insight-content h3 { font-size: 1.5rem !important; }

                .insight-content p {
                    margin-bottom: 2.5rem !important; /* Forced space between paragraphs */
                    display: block !important;
                    line-height: 2 !important;
                    color: #1e293b !important; /* slate-800 */
                }

                .insight-content strong {
                    font-weight: 900 !important;
                    color: #0f172a !important;
                    text-decoration: underline !important;
                    text-decoration-color: #BDB76B !important;
                    text-decoration-thickness: 4px !important;
                }

                .insight-content ul {
                    margin-bottom: 2.5rem !important;
                    list-style-type: disc !important;
                    padding-left: 1.5rem !important;
                }

                .insight-content li {
                    margin-bottom: 1rem !important;
                }
            </style>

            <article class="insight-content max-w-none text-xl">
                {!! str($insight)->markdown() !!}
            </article>
            
            <!-- Metadata Footer -->
            <div class="mt-20 pt-10 border-t-2 border-slate-100 flex flex-wrap gap-12">
                <div class="flex flex-col">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Risk Assessment</span>
                    <span class="text-sm font-bold text-rose-600">Immediate Review Required</span>
                </div>
                <div class="flex flex-col border-l-2 border-slate-200 pl-12">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Data Source</span>
                    <span class="text-sm font-bold text-slate-900 italic">2026 Comparative Budget Analytics</span>
                </div>
            </div>
        @else
            <div class="py-24 text-center border-2 border-dashed border-slate-100 rounded-xl">
                <p class="text-slate-300 font-bold text-2xl uppercase tracking-tighter">Awaiting Forensic Data Input</p>
            </div>
        @endif
    </div>
</div>