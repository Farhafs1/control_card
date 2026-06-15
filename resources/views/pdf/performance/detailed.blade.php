<!DOCTYPE html>
<html>
<head>
    <title>Detailed Performance Report - {{ $categoryName }}</title>
    <style>
        /* 1. Set A4 Size and Tighten Margins */
        @page { 
            size: a4 landscape; 
            /* 0.8cm top/bottom gives it that sleek professional look without huge gaps */
            margin: 0.8cm 1cm; 
        }

        /* 2. Base Typography */
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 10px; 
            color: #333; 
            margin: 0;
            padding: 0;
        }

        /* 3. Header Section */
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #1a4731; 
            padding-bottom: 10px; 
        }
        .header h2 { margin: 0; color: #1a4731; font-size: 18px; }
        .header h3 { margin: 5px 0; font-size: 12px; text-transform: uppercase; }
        .header p { margin: 2px 0; color: #666; }
        
        /* 4. Table Styling */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
            table-layout: fixed; 
        }
        th, td { 
            border: 0.5px solid #ccc; 
            padding: 6px 4px; 
            word-wrap: break-word; 
        }
        
        th { 
            background-color: #1a4731; 
            color: white; 
            font-weight: bold; 
            text-transform: uppercase; 
            font-size: 9px; 
        }
        
        .mda-row { 
            background-color: #f0f0f0; 
            font-weight: bold; 
            color: #000; 
            border-top: 1.5px solid #333; 
        }
        
        .mda-name { 
            font-size: 10px; 
            padding: 8px; 
            text-transform: uppercase; 
        }
        
        /* 5. Utility Classes */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .percentage { font-weight: bold; color: #1a4731; }
        .over-budget { color: #b91c1c; } 

        /* 6. Footer Fixed at Bottom */
        footer {
            position: fixed; 
            bottom: -15px; /* Pulls it closer to the bottom edge */
            left: 0px; 
            right: 0px;
            height: 30px; 
            text-align: center;
            font-size: 8px;
            color: #999;
        }
    </style>

    <script type="text/php">
        if ( isset($pdf) ) {
            $font = $fontMetrics->get_font("helvetica", "normal");
            // Center-aligned: 270 is roughly center of A4 portrait
            $pdf->page_text(270, 815, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 8, array(0.5, 0.5, 0.5));
        }
    </script>


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
            @endif
        </div>

        <div style="text-align: center; width: 80%; margin: 0 auto;">
            <h1 class="govt-title" style="margin: 0; letter-spacing: 2px;">KATSINA STATE GOVERNMENT</h1>
            <h2 style="margin: 5px 0; font-size: 14px; font-weight: normal; text-transform: uppercase; color: #444;">
                Ministry of Budget and Economic Planning
            </h2>
            <div class="report-subtitle" style="font-weight: bold; color: #1a4731; font-size: 13px;">
                Detailed Budget Performance Report ({{ strtoupper($categoryName) }})
            </div>
            <div style="font-size: 11px; margin-top: 5px; color: #555;">
                Quarter {{ $quarter }} - Fiscal Year {{ $year }}
            </div>
        </div>

        <div style="position: absolute; right: 0; top: 70px; text-align: right; font-size: 9px; color: #666; line-height: 1.2;">
            <strong>Generated:</strong> {{ $date }}<br>
            <strong>Status:</strong> Official Ledger
        </div>
    </header>

    <table>
        <thead>
            <tr>
                <th width="12%">Code</th>
                <th width="33%">Subhead Description</th>
                <th width="15%" class="text-right">Approved Provision</th>
                <th width="15%" class="text-right">Actual Q{{ $quarter }} (Total)</th>
                <th width="15%" class="text-right">Balance</th>
                <th width="10%" class="text-center">% Perf.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results as $mda)
                @php
                    // Normalize to object
                    $mda = (object) $mda;
                    // Ensure subheads is always a collection, regardless of input type
                    $subheads = isset($mda->subheads) ? collect($mda->subheads) : collect([]);

                    // 1. Calculate MDA Totals using the collection
                    $mdaProv = $subheads->sum(fn($s) => 
                        ((float)($s->approved_provision ?? $s['approved_provision'] ?? 0)) + 
                        ((float)($s->additional_provision ?? $s['additional_provision'] ?? 0))
                    );
                    $mdaActual = $subheads->sum(fn($s) => 
                        (float)($s->releases_sum_amount ?? $s['releases_sum_amount'] ?? 0)
                    );
                    $mdaBalance = $mdaProv - $mdaActual;
                    $mdaPerf = $mdaProv > 0 ? ($mdaActual / $mdaProv) * 100 : 0;
                @endphp

                <tr class="mda-row">
                    <td colspan="2" class="mda-name">{{ $mda->mda_code ?? ($mda['mda_code'] ?? 'N/A') }} - {{ $mda->name ?? ($mda['name'] ?? 'N/A') }} </td>
                    <td class="text-right mda-name">{{ number_format($mdaProv, 2) }}</td>
                    <td class="text-right mda-name">{{ number_format($mdaActual, 2) }}</td>
                    <td class="text-right mda-name">{{ number_format($mdaBalance, 2) }}</td>
                    <td class="text-center mda-name">{{ number_format($mdaPerf, 1) }}%</td>
                </tr>

                @foreach($subheads as $sh)
                    @php
                        // 1. Ensure $sh is an array for consistent access
                        // If it's a Model, toArray() converts it. If it's an array, it stays an array.
                        $sh = ($sh instanceof \Illuminate\Database\Eloquent\Model) ? $sh->toArray() : (array) $sh;

                        // 2. Now use only array syntax consistently
                        $approved = (float)($sh['approved_provision'] ?? 0);
                        $additional = (float)($sh['additional_provision'] ?? 0);
                        $provision = $approved + $additional;
                        
                        $actualSum = (float)($sh['releases_sum_amount'] ?? 0); 
                        $balance = $provision - $actualSum;
                        
                        $perf = $provision > 0 ? ($actualSum / $provision) * 100 : 0;
                    @endphp

                    <tr>
                        <td style="font-family: monospace;">{{ $sh['subhead_code'] ?? 'N/A' }}</td>
                        <td>{{ $sh['description'] ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format($provision, 2) }}</td>
                        <td class="text-right">{{ number_format($actualSum, 2) }}</td>
                        <td class="text-right">{{ number_format($balance, 2) }}</td>
                        <td class="text-center percentage {{ $perf > 100 ? 'over-budget' : '' }}">
                            {{ number_format($perf, 1) }}%
                        </td>
                    </tr>
                @endforeach
            @endforeach        
        </tbody>
    </table>

    <div style="margin-top: 20px; font-style: italic; color: #666; font-size: 8px;">
        * Actual Q{{ $quarter }} (Total) represents the sum of all releases within the selected quarter date range.<br>
        * % Perf. is calculated as (Total Releases / Annual Provision) * 100.
    </div>
    <!-- <footer>
        Official Budget Performance Report - Page <span class="page-number"></span>
    </footer> -->
    <script type="text/php">
        if ( isset($pdf) ) {
            // Set font
            $font = $fontMetrics->get_font("helvetica", "normal");
            
            // Coordinates for Portrait (X: 270 is center, Y: 820 is bottom)
            // Adjust 270 to 400+ if you are using Landscape
            $pdf->page_text(270, 820, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 8, array(0.5, 0.5, 0.5));
        }
    </script>
</body>
</html>