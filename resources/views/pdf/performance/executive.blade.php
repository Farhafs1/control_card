<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 30px; }
        
        /* DejaVu Sans is essential for the Naira (₦) symbol to render correctly in DomPDF */
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            color: #1a202c; 
            line-height: 1.4; 
            font-size: 14px;
        }
        
        /* Header Styling */
        .header-table { width: 100%; border-bottom: 3px solid #064e3b; padding-bottom: 15px; margin-bottom: 20px; }
        .logo { width: 70px; }
        .state-title { font-size: 20px; font-weight: bold; color: #1a202c; text-transform: uppercase; margin: 0; }
        .report-subtitle { font-size: 13px; font-weight: bold; color: #064e3b; margin: 5px 0 0 0; }
        .meta-text { text-align: right; font-size: 9px; color: #718096; font-weight: bold; text-transform: uppercase; }

        /* Table Styling */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background-color: #064e3b; color: white; font-size: 12px; padding: 10px 5px; text-transform: uppercase; border: 1px solid #064e3b; }
        td { border: 1px solid #e2e8f0; padding: 8px 5px; font-size: 11px; text-align: right; }
        
        .label-cell { text-align: left; font-weight: bold; background-color: #f8fafc; color: #2d3748; }
        .approved-cell { font-weight: bold; background-color: #f0fdf4; }
        
        .perf-badge { 
            background-color: #064e3b; color: white; padding: 2px 4px; border-radius: 3px; 
            font-size: 8px; font-weight: bold; display: inline-block; margin-top: 4px; 
        }
        
        .q-subtext { display: block; font-size: 10px; color: #718096; font-weight: bold; margin-top: 2px; }

        /* Footer / Totals */
        .total-row { background-color: #1a202c; color: white; font-weight: bold; }
        .total-row td { border: 1px solid #1a202c; color: white; padding: 12px 5px; }
        .grand-perf { background-color: #15803d !important; border: 1px solid #15803d !important; }
        
        .report-footer { margin-top: 30px; font-size: 8px; color: #718096; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>

    <header style="position: relative; border-bottom: 8px solid #1a4731; padding-bottom: 15px; margin-bottom: 30px; min-height: 100px;">
        <div style="position: absolute; left: 0; top: 0;">
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

        <div style="text-align: center; width: 85%; margin: 0 auto;">
            <h1 style="margin: 0; letter-spacing: 2px; font-size: 24px; font-weight: bold; color: #1a1a1a; font-family: 'Times New Roman', serif;">
                KATSINA STATE GOVERNMENT
            </h1>
            <h2 style="margin: 5px 0; font-size: 14px; font-weight: normal; text-transform: uppercase; color: #444;">
                Ministry of Budget and Economic Planning
            </h2>
            
            <div style="font-weight: bold; color: #1a4731; font-size: 15px; margin-top: 5px;">
                EXECUTIVE BUDGET PERFORMANCE OVERVIEW
            </div>
            
            <div style="font-size: 12px; margin-top: 5px; color: #555; font-weight: bold;">
                FISCAL YEAR {{ $year }}
            </div>
        </div>

        <div style="position: absolute; right: 0; top: 70px; text-align: right; font-size: 9px; color: #666; line-height: 1.4;">
            <strong>Generated:</strong> {{ $date }}<br>
            <strong>System:</strong> Budget Control System
        </div>
    </header>
    
    <table>
        <thead>
            <tr>
                <th style="text-align:left; width: 13%;">Budget Category</th>
                <th style="width: 13%;">Approved Provision</th>
                <th style="width: 13%;">1st Quarter</th>
                <th style="width: 13%;">2nd Quarter</th>
                <th style="width: 13%;">3rd Quarter</th>
                <th style="width: 13%;">4th Quarter</th>
                <th style="width: 15%; background-color: #059669;">Overall Perf.</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $gApp = 0; $gQ1 = 0; $gQ2 = 0; $gQ3 = 0; $gQ4 = 0; $gTot = 0; 
            @endphp

            @foreach($summary as $row)
                @php
                    // Ensure $row is treated as an object for consistent key access
                    $row = (object) $row;

                    // Smart fallback: Check for budget (Officer) first, then total_prov (Admin)
                    $app = (float)($row->budget ?? ($row->total_prov ?? 0));
                    
                    $q1  = (float)($row->q1 ?? 0);
                    $q2  = (float)($row->q2 ?? 0);
                    $q3  = (float)($row->q3 ?? 0);
                    $q4  = (float)($row->q4 ?? 0);
                    
                    $tot = (float)($row->total ?? 0);
                    $perf = (float)($row->perf ?? 0);

                    $gApp += $app; $gQ1 += $q1; $gQ2 += $q2; $gQ3 += $q3; $gQ4 += $q4; $gTot += $tot;
                @endphp
                
                <tr>
                    <td class="label-cell">{{ $row->label ?? $row['label'] }}</td>
                    <td class="approved-cell">&#8358;{{ number_format($app, 2) }}</td>
                    
                    <td>
                        &#8358;{{ number_format($q1, 2) }}
                        <span class="q-subtext">{{ $app > 0 ? number_format(($q1/$app)*100, 1) : 0 }}%</span>
                    </td>
                    <td>
                        &#8358;{{ number_format($q2, 2) }}
                        <span class="q-subtext">{{ $app > 0 ? number_format(($q2/$app)*100, 1) : 0 }}%</span>
                    </td>
                    <td>
                        &#8358;{{ number_format($q3, 2) }}
                        <span class="q-subtext">{{ $app > 0 ? number_format(($q3/$app)*100, 1) : 0 }}%</span>
                    </td>
                    <td>
                        &#8358;{{ number_format($q4, 2) }}
                        <span class="q-subtext">{{ $app > 0 ? number_format(($q4/$app)*100, 1) : 0 }}%</span>
                    </td>

                    <td style="background-color: #f0fdfa;">
                        <div style="font-weight: bold; color: #064e3b;">&#8358;{{ number_format($tot, 2) }}</div>
                        <div class="perf-badge">{{ number_format($perf, 1) }}%</div>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td class="label-cell" style="background-color: #1a202c; color: white;">GRAND TOTAL</td>
                <td>&#8358;{{ number_format($gApp, 2) }}</td>
                <td>&#8358;{{ number_format($gQ1, 2) }}</td>
                <td>&#8358;{{ number_format($gQ2, 2) }}</td>
                <td>&#8358;{{ number_format($gQ3, 2) }}</td>
                <td>&#8358;{{ number_format($gQ4, 2) }}</td>
                <td class="grand-perf">
                    <div style="font-size: 10px;">&#8358;{{ number_format($gTot, 2) }}</div>
                    <div style="font-size: 7px; color: #a7f3d0;">State Perf: {{ $gApp > 0 ? number_format(($gTot/$gApp)*100, 1) : 0 }}%</div>
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="report-footer">
        <p>Source: Ministry of Budget and Economic Planning. Generated: {{ date('d/m/Y H:i') }}</p>
        <p><i>Note: Performance percentages are calculated based on the Total Provision (Approved + Additional).</i></p>
    </div>

</body>
</html>