<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 30px; }
        
        /* DejaVu Sans for Naira (₦) symbol */
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            color: #1a202c; 
            line-height: 1.4; 
            font-size: 9px; /* Slightly smaller to fit the extra columns */
        }
        
        /* Header Styling - Matching Executive */
        .header-container { position: relative; border-bottom: 8px solid #1a4731; padding-bottom: 15px; margin-bottom: 30px; min-height: 100px; }
        .logo-box { position: absolute; left: 0; top: 0; }
        .center-titles { text-align: center; width: 85%; margin: 0 auto; }
        .state-title { margin: 0; letter-spacing: 2px; font-size: 24px; font-weight: bold; color: #1a1a1a; font-family: 'Times New Roman', serif; }
        .ministry-title { margin: 5px 0; font-size: 14px; font-weight: normal; text-transform: uppercase; color: #444; }
        .report-title { font-weight: bold; color: #1a4731; font-size: 15px; margin-top: 5px; }
        .meta-box { position: absolute; right: 0; top: 70px; text-align: right; font-size: 9px; color: #666; line-height: 1.4; }

        /* Table Styling - Matching Executive */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; table-layout: fixed; }
        th { background-color: #064e3b; color: white; font-size: 12px; padding: 10px 4px; text-transform: uppercase; border: 1px solid #064e3b; text-align: right; }
        th:first-child { text-align: left; width: 18%; }
        
        td { border: 1px solid #e2e8f0; padding: 8px 4px; font-size: 12px; text-align: right; }
        
        .label-cell { text-align: left; font-weight: bold; background-color: #f8fafc; color: #2d3748; }
        .provision-cell { font-weight: bold; background-color: #f0fdf4; }
        
        .perf-badge { 
            background-color: #064e3b; color: white; padding: 2px 4px; border-radius: 3px; 
            font-size: 10px; font-weight: bold; display: inline-block; margin-top: 4px; 
        }

        /* Footer / Totals - Matching Executive */
        .total-row { background-color: #1a202c; color: white; font-weight: bold; }
        .total-row td { border: 1px solid #1a202c; color: white; padding: 12px 4px; }
        .grand-perf { background-color: #15803d !important; border: 1px solid #15803d !important; text-align: center; }
        
        /* Signatures Section */
        .signature-table { margin-top: 40px; width: 100%; border: none; }
        .sig-line { border-top: 1px solid #333; width: 180px; margin: 0 auto 5px auto; }
        .sig-text { font-size: 8px; text-align: center; }

        .report-footer { margin-top: 30px; font-size: 8px; color: #718096; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>

    <header class="header-container">
        <div class="logo-box">
            @php
                $path = public_path('assets/images/katsina-crest.png');
                $base64 = file_exists($path) ? 'data:image/' . pathinfo($path, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($path)) : null;
            @endphp
            @if($base64)
                <img src="{{ $base64 }}" style="height: 90px; width: auto;">
            @else
                <div style="height: 80px; width: 80px; border: 1px dashed #ccc; line-height: 80px; text-align: center; font-size: 8px;">LOGO</div>
            @endif
        </div>

        <div class="center-titles">
            <h1 class="state-title">KATSINA STATE GOVERNMENT</h1>
            <h2 class="ministry-title">Ministry of Budget and Economic Planning</h2>
            <div class="report-title">QUARTERLY PERFORMANCE SUMMARY (Q{{ $quarter }})</div>
            <div style="font-size: 12px; margin-top: 5px; color: #555; font-weight: bold;">FISCAL YEAR {{ $year }}</div>
        </div>

        <div class="meta-box">
            <strong>Date:</strong> {{ date('d M, Y') }}<br>
            <strong>Report:</strong> Official Quarterly Summary
        </div>
    </header>
    
    <table>
        <thead>
            <tr>
                <th style="text-align:left; width: 15%;">Budget Category</th>
                <th style="width: 15%;">Approved Provision </th>
                <th style="width: 15%;">Additional Provision</th>
                <th style="width: 17%;">Total Provision</th>
                <th style="background-color: #059669;">Actual Q{{ $quarter }} Performance</th>
                <th style="width: 16%;">Balance</th>
                <th style="width: 7%;">Perf. (%)</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $gApp = 0; $gAdd = 0; $gTot = 0; $gAct = 0; 
            @endphp

            {{-- 1. Ensure you are looping over the nested key --}}
            @php
                // Detect if we are using the nested (Officer) or flat (Admin) structure
                $rows = isset($summary['full_list']) ? $summary['full_list'] : $summary;
            @endphp

            @foreach($rows as $row)
                @php
                    // Ensure we are working with an array for consistent key access
                    $row = (array) $row;
                    
                    $app = $row['approved'] ?? ($row['budget'] ?? 0);
                    $add = $row['additional'] ?? 0;
                    $total_prov = $row['total_prov'] ?? ($app + $add);
                    $actual = $row['actual'] ?? ($row['total'] ?? 0);
                    $balance = $row['balance'] ?? ($total_prov - $actual);
                    $perf = $row['perf'] ?? 0;

                    $gApp += $app; $gAdd += $add; $gTot += $total_prov; $gAct += $actual;
                @endphp
                
                <tr>
                    <td class="label-cell">{{ $row['label'] ?? 'N/A' }}</td>
                    <td>&#8358;{{ number_format($app, 2) }}</td>
                    <td style="color: #2563eb;">&#8358;{{ number_format($add, 2) }}</td>
                    <td class="provision-cell">&#8358;{{ number_format($total_prov, 2) }}</td>
                    <td style="background-color: #f0fdfa; font-weight: bold; color: #064e3b;">
                        &#8358;{{ number_format($actual, 2) }}
                    </td>
                    <td style="color: {{ $balance < 0 ? '#dc2626' : '#4b5563' }};">
                        &#8358;{{ number_format($balance, 2) }}
                    </td>
                    <td style="text-align: center;">
                        <div class="perf-badge">{{ number_format($perf, 1) }}%</div>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td class="label-cell" style="background-color: #1a202c; color: white;">GRAND TOTAL</td>
                <td>&#8358;{{ number_format($gApp, 2) }}</td>
                <td>&#8358;{{ number_format($gAdd, 2) }}</td>
                <td>&#8358;{{ number_format($gTot, 2) }}</td>
                <td>&#8358;{{ number_format($gAct, 2) }}</td>
                <td>&#8358;{{ number_format($gTot - $gAct, 2) }}</td>
                <td class="grand-perf">
                    <div style="font-size: 12px; color: #a7f3d0; font-weight: bold;">
                        {{ $gTot > 0 ? number_format(($gAct / $gTot) * 100, 1) : 0 }}%
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- Official Signatures Section --}}
    <table class="signature-table">
        <tr>
            <td style="border: none; text-align: left;">
                <div class="sig-line"></div>
                <div class="sig-text">
                    <strong>Director of Budget</strong><br>
                    Ministry of Budget & Economic Planning
                </div>
            </td>
            <td style="border: none;"></td>
            <td style="border: none; text-align: right;">
                <div class="sig-line"></div>
                <div class="sig-text">
                    <strong>Hon. Commissioner</strong><br>
                    Ministry of Budget & Economic Planning
                </div>
            </td>
        </tr>
    </table>

    <div class="report-footer">
        <p>Source: Ministry of Budget and Economic Planning. | System: FCCS Katsina State</p>
        <p><i>Note: "Actual Q{{ $quarter }}" represents the total performance for the selected quarter. Balance is the remaining annual provision.</i></p>
    </div>

</body>
</html>