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
            font-size: 10.5px; 
        }
        
        /* Header Styling - Adopted from Overview */
        .header-container { position: relative; border-bottom: 8px solid #1a4731; padding-bottom: 15px; margin-bottom: 25px; min-height: 100px; }
        .logo-box { position: absolute; left: 0; top: 0; }
        .center-titles { text-align: center; width: 85%; margin: 0 auto; }
        .state-title { margin: 0; letter-spacing: 2px; font-size: 22px; font-weight: bold; color: #1a1a1a; font-family: 'Times New Roman', serif; }
        .ministry-title { margin: 5px 0; font-size: 13px; font-weight: normal; text-transform: uppercase; color: #444; }
        .report-title { font-weight: bold; color: #1a4731; font-size: 14px; margin-top: 5px; }
        .meta-box { position: absolute; right: 0; top: 70px; text-align: right; font-size: 8px; color: #666; }

        /* Table Styling */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        th { background-color: #064e3b; color: white; font-size: 11px; padding: 10px 4px; text-transform: uppercase; border: 1px solid #064e3b; text-align: right; }
        th.mda-col { text-align: left; width: 28%; }
        th.rank-col { width: 6%; text-align: center; }
        
        td { border: 1px solid #e2e8f0; padding: 8px 4px; text-align: right; vertical-align: middle; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        
        .rank-badge { 
            background-color: #f1f5f9; 
            color: #475569; 
            padding: 4px; 
            border-radius: 50%; 
            font-weight: bold; 
            display: inline-block;
            width: 20px;
        }

        .mda-code { font-family: monospace; color: #64748b; font-size: 11px; display: block; margin-top: 2px; }
        .total-cell { background-color: #f0fdf4; font-weight: bold; color: #064e3b; }
        
        /* Relative Performance Bar */
        .bar-container { width: 50px; background-color: #e2e8f0; height: 4px; border-radius: 2px; float: right; margin-top: 5px; overflow: hidden; }
        .bar-fill { background-color: #15803d; height: 100%; }

        /* Signatures Section */
        .signature-table { margin-top: 30px; width: 100%; border: none; }
        .sig-line { border-top: 1px solid #333; width: 160px; margin: 0 auto 5px auto; }
        .sig-text { font-size: 8px; text-align: center; }

        .report-footer { margin-top: 20px; font-size: 7.5px; color: #718096; border-top: 1px solid #e2e8f0; padding-top: 8px; }
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
                <img src="{{ $base64 }}" style="height: 85px; width: auto;">
            @else
                <div style="height: 70px; width: 70px; border: 1px dashed #ccc; line-height: 70px; text-align: center; font-size: 8px;">LOGO</div>
            @endif
        </div>

        <div class="center-titles">
            <h1 class="state-title">KATSINA STATE GOVERNMENT</h1>
            <h2 class="ministry-title">Ministry of Budget and Economic Planning</h2>
            <div class="report-title">MDA EXPENDITURE RANKING REPORT (Q{{ $quarter }})</div>
            <div style="font-size: 11px; margin-top: 5px; color: #555; font-weight: bold;">FISCAL YEAR {{ $year }}</div>
        </div>

        <div class="meta-box">
            <strong>Date:</strong> {{ date('d M, Y') }}<br>
            <strong>Report Type:</strong> Expenditure Ranking
        </div>
    </header>
    
    <table>
        <thead>
            <tr>
                <th class="rank-col">Rank</th>
                <th class="mda-col">MDA / Organization Name</th>
                <th>Revenue</th>
                <th>Personnel</th>
                <th>Overhead</th>
                <th>Capital</th>
                <th style="background-color: #059669; width: 15%;">Total Spend</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $topSpender = count($results) > 0 ? ($results[0]['total_spend'] ?? 1) : 1; 
                if($topSpender == 0) $topSpender = 1;
            @endphp

            @foreach($results as $index => $mda)
                <tr>
                    <td class="text-center">
                        <span class="rank-badge">{{ $loop->iteration }}</span>
                    </td>
                    <td class="text-left">
                        <div style="font-weight: bold;">{{ $mda['mda_name'] }}</div>
                        <span class="mda-code">{{ $mda['mda_code'] }}</span>
                    </td>
                    <td>&#8358;{{ number_format($mda['revenue'], 2) }}</td>
                    <td>&#8358;{{ number_format($mda['personnel'], 2) }}</td>
                    <td>&#8358;{{ number_format($mda['overhead'], 2) }}</td>
                    <td>&#8358;{{ number_format($mda['capital'], 2) }}</td>
                    <td class="total-cell">
                        &#8358;{{ number_format($mda['total_spend'], 2) }}
                        <!-- <div class="bar-container">
                            <div class="bar-fill" style="width: {{ ($mda['total_spend'] / $topSpender) * 100 }}%"></div>
                        </div> -->
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Official Signatures Section adopted from Overview --}}
    <table class="signature-table">
        <tr>
            <td style="border: none; text-align: left;">
                <div class="sig-line"></div>
                <div class="sig-text">
                    <strong>Director of Budget</strong><br>
                    Ministry of Budget and Economic Planning
                </div>
            </td>
            <td style="border: none;"></td>
            <td style="border: none; text-align: right;">
                <div class="sig-line"></div>
                <div class="sig-text">
                    <strong>Hon. Commissioner</strong><br>
                    Ministry of Budget and Economic Planning
                </div>
            </td>
        </tr>
    </table>

    <div class="report-footer">
        <p>Source: Ministry of Budget and Economic Planning. Generated by BCS Katsina. | Note: Ranking is sorted by Total Cash Expenditure in Q{{ $quarter }}.</p>
    </div>

</body>
</html>