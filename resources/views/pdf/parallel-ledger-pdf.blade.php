<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Budget Analytics Engine Ledger Report</title>
    <style>
        @page { margin: 35px 40px; }
        body {
            /* FIX: Use DejaVu Sans as it includes the native Unicode Naira character matrix */
            font-family: 'DejaVu Sans', sans-serif;
            color: #1e293b;
            font-size: 12px; /* Marginally reduced from 11px to balance spacing since DejaVu reads wider */
            line-height: 1.4;
        }

        /* Top System Meta Header Banner */
        .header-matrix {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-subtitle {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
        }
        .meta-badge {
            text-align: right;
            font-size: 10px;
            color: #334155;
        }
        .meta-badge font {
            font-weight: bold;
            color: #4f46e5;
        }

        /* Summary Stat Boxes Container Row */
        .stats-grid {
            width: 100%;
            margin-bottom: 25px;
        }
        .stat-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            border-radius: 6px;
            width: 23%;
        }
        .stat-card-net-positive {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 10px 12px;
            border-radius: 6px;
            width: 23%;
        }
        .stat-card-net-negative {
            background-color: #fef2f2;
            border: 1px solid #fca5a5;
            padding: 10px 12px;
            border-radius: 6px;
            width: 23%;
        }
        .stat-label {
            font-size: 8px;
            text-transform: uppercase;
            font-weight: bold;
            color: #64748b;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .stat-value {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            /* FIX: Explicitly assign DejaVu Sans here so the font engine captures the symbol inside the cards */
            font-family: 'DejaVu Sans', sans-serif;
        }

        /* Main Data Ledger Matrix Table */
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .ledger-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #0f172a;
            letter-spacing: 0.5px;
        }
        .ledger-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .ledger-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        
        /* Specialized Alignments and Monospace Masks */
        .text-mono {
            /* FIX: Change from Courier to DejaVu Sans Mono to safeguard tables */
            font-family: 'DejaVu Sans Mono', monospace;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        
        /* Percentage indicator badges */
        .rate-badge {
            font-weight: bold;
            padding: 2px 5px;
            background-color: #e2e8f0;
            color: #1e293b;
            border-radius: 4px;
            font-size: 9px;
        }

        /* Footer Stamp Page Numbering rules */
        .footer-stamp {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 15px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    {{-- System Layout Header Meta Block --}}
    <table class="header-matrix">
        <tr>
            <td>
                <div class="header-title">Budget Analytics Engine Workspace Ledger</div>
                <div class="header-subtitle">Automated Transactional Summary </div>
            </td>
            <td class="meta-badge">
                Execution Period: <font>{{ $quarterLabel }}</font><br>
                Dimension Axis: <font>{{ $dimensionAxis }}</font>
            </td>
        </tr>
    </table>

    {{-- Core Workspace Total Capital Financial Balances Rows --}}
    <table class="stats-grid" cellspacing="10">
        <tr>
            <td class="stat-card">
                <div class="stat-label">Opening Balance Base</div>
                <div class="stat-value">₦{{ number_format($stats['opening_balance'] ?? 0, 2) }}</div>
            </td>
            
            <td class="stat-card">
                <div class="stat-label">Total Revenue (Inflows Actual)</div>
                <div class="stat-value" style="color: #16a34a;">₦{{ number_format($stats['revenue']['actual'] ?? 0, 2) }}</div>
            </td>
            
            <td class="stat-card">
                <div class="stat-label">Total Expenditure (Outflows Actual)</div>
                <div class="stat-value" style="color: #dc2626;">₦{{ number_format($stats['expenditure']['actual'] ?? 0, 2) }}</div>
            </td>
            
            <td class="{{ ($stats['net_cash_position'] ?? 0) >= 0 ? 'stat-card-net-positive' : 'stat-card-net-negative' }}">
                <div class="stat-label">Net Treasury Position</div>
                <div class="stat-value" style="color: {{ ($stats['net_cash_position'] ?? 0) >= 0 ? '#15803d' : '#b91c1c' }};">
                    ₦{{ number_format($stats['net_cash_position'] ?? 0, 2) }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Main Detailed Row Analytics Grid --}}
    <table class="ledger-table">
        <thead>
            <tr>
                <th style="width: 38%;">Line Item Component Axis / Name</th>
                <th style="width: 16%; text-align: right;">Approved Provision (Budget)</th>
                <th style="width: 16%; text-align: right;">Actual Engine Performance</th>
                <th style="width: 16%; text-align: right;">Available Balance Variance</th>
                <th style="width: 14%; text-align: center;">Performance Rate</th>
            </tr>
        </thead>
        <tbody>
            @forelse($performance as $row)
                @php
                    $name = $row['name'] ?? $row['category'] ?? $row['mda_name'] ?? 'Unknown Line Item';
                    $budget = (float)($row['budget'] ?? 0);
                    $actual = (float)($row['actual'] ?? 0);
                    $variance = $budget - $actual;
                    $rate = $budget > 0 ? ($actual / $budget) * 100 : 0;
                @endphp
                <tr>
                    <td class="font-bold" style="color: #334155;">• {{ $name }}</td>
                    <td class="text-mono text-right">₦{{ number_format($budget, 2) }}</td>
                    <td class="text-mono text-right" style="font-weight: bold; color: {{ $actual > $budget ? '#b91c1c' : '#1e293b' }};">
                        ₦{{ number_format($actual, 2) }}
                    </td>
                    <td class="text-mono text-right" style="color: {{ $variance < 0 ? '#b91c1c' : '#475569' }};">
                        ₦{{ number_format($variance, 2) }}
                    </td>
                    <td class="text-center">
                        <span class="text-mono rate-badge" style="{{ $rate > 100 ? 'background-color: #fef3c7; color: #b45309;' : '' }}">
                            {{ number_format($rate, 1) }}%
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 30px; color: #94a3b8; font-style: italic;">
                        No record streams localized within the current tracking vectors.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Print Timestamp Signature Bottom Overlay Badge --}}
    <div class="footer-stamp">
        Generated via Control Card Budget Analytics Matrix Engine • System Archive Log • Page 1 of 1
    </div>

</body>
</html>