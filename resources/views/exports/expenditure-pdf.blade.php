<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 15px; margin-bottom: 20px; }
        .gov-title { font-size: 22px; font-weight: bold; margin-bottom: 5px; color: #0f172a; }
        .report-type { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
        
        /* AI Insights Styling */
        .ai-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; margin: 20px 0; border-radius: 12px; }
        .ai-title { color: #065f46; font-weight: bold; font-size: 14px; margin-bottom: 15px; border-bottom: 1px solid #065f46; padding-bottom: 5px; text-transform: uppercase; }
        
        /* Markdown Conversions Styling */
        .audit-text h4 { color: #0f172a; font-size: 16px; margin-top: 20px; margin-bottom: 8px; text-decoration: underline; }
        .audit-text strong { color: #000; font-weight: bold; }
        .audit-text em { font-style: italic; color: #475569; }
        .audit-text ul { padding-left: 20px; margin-top: 10px; list-style-type: square; }
        .audit-text li { margin-bottom: 6px; }

        /* Table Styling */
        table { width: 100%; border-collapse: collapse; margin-top: 25px; font-size: 10px; }
        th { background: #0f172a; color: white; padding: 10px 8px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #e2e8f0; padding: 8px; vertical-align: top; }
        .total-row { font-weight: bold; background: #f1f5f9; font-size: 11px; }
        .mda-column { font-weight: bold; color: #334155; }
    </style>
</head>
<body>
    <div class="header">
        <p class="gov-title">KATSINA STATE GOVERNMENT</p>
        <p class="report-type">Executive Expenditure Audit Intelligence Report</p>
        <p style="font-size: 10px; margin-top: 5px;">Report Cycle: {{ now()->format('F Y') }} | Generated: {{ $date }}</p>
    </div>

    @if($aiAnalysis)
    <div class="ai-box">
        <div class="ai-title">EXECUTIVE AUDIT SUMMARY (GEMINI 2.5 ANALYSIS)</div>
        <div class="audit-text" style="font-size: 12px;">
            @php
                $content = $aiAnalysis;
                
                // 1. Convert Headers (#### or ###) to <h4>
                $content = preg_replace('/#{3,4}\s*(.*)/', '<h4>$1</h4>', $content);
                
                // 2. Convert Bold (**text**) to <strong>
                $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
                
                // 3. Convert Italics (*text* or _text_) to <em>
                $content = preg_replace('/\*([^\*]+)\*/', '<em>$1</em>', $content);
                
                // 4. Convert Bullet Points (- text or * text) to <li>
                // We handle the start of lines in a multiline string
                $content = preg_replace('/^\s*[\-\*]\s*(.*)/m', '<li>$1</li>', $content);
                
                // 5. Wrap <li> groups in <ul>
                if (strpos($content, '<li>') !== false) {
                    // Simple replacement to close/open lists; for complex MD we'd use a parser, but this works for AI output
                    $content = preg_replace('/(<li>.*<\/li>)+/s', '<ul>$0</ul>', $content);
                }
            @endphp
            {!! nl2br($content) !!}
        </div>
    </div>
    @endif

    <h3 style="font-size: 14px; border-left: 4px solid #0f172a; padding-left: 10px; margin-top: 30px;">Detailed Release Records</h3>
    <table>
        <thead>
            <tr>
                <th width="12%">Date</th>
                <th width="25%">MDA</th>
                <th width="43%">Narration / Purpose</th>
                <th width="20%" style="text-align: right;">Amount (₦)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($releases as $release)
            <tr>
                <td>{{ \Carbon\Carbon::parse($release->release_date)->format('d/m/y') }}</td>
                <td class="mda-column">{{ $release->mda->name }}</td>
                <td>{{ $release->narration }}</td>
                <td style="text-align: right;">{{ number_format($release->amount, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">CUMULATIVE DISBURSED:</td>
                <td style="text-align: right;">₦{{ number_format($total, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 50px; font-size: 9px; color: #94a3b8; text-align: center;">
        <p>This is a computer-generated audit brief powered by MOBEP Systems. <br> 
        Confidential for Government Use Only.</p>
    </div>
</body>
</html>