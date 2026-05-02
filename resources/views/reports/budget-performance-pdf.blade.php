<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            size: A4;
            margin: 20mm 15mm;
            @bottom-right {
                content: "Page " counter(page) " of " counter(pages);
                font-size: 9pt;
                color: #64748b;
            }
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
        }

        /* Official Header */
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10mm;
            margin-bottom: 10mm;
        }

        .gov-logo {
            width: 80px;
            height: auto;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 18pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
            margin: 0;
        }

        .report-subtitle {
            font-size: 11pt;
            color: #64748b;
            margin-top: 5px;
        }

        /* Summary Cards Grid (PDF Optimized) */
        .stats-grid {
            display: table;
            width: 100%;
            border-spacing: 10px;
            margin-bottom: 10mm;
        }

        .stat-card {
            display: table-cell;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            width: 25%;
        }

        .stat-label {
            font-size: 8pt;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 14pt;
            font-weight: 800;
            color: #0f172a;
        }

        /* Table Design */
        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
        }

        thead {
            display: table-header-group; /* Ensures headers repeat on new pages */
        }

        th {
            background-color: #0f172a;
            color: white;
            font-size: 9pt;
            font-weight: bold;
            padding: 10px;
            text-align: left;
            text-transform: uppercase;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        td {
            padding: 8px 10px;
            font-size: 9pt;
            border-bottom: 1px solid #e2e8f0;
        }

        .text-right { text-align: right; }
        
        /* Health Badges for Print */
        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8pt;
        }
        .badge-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

    </style>
</head>
<body>

    <div class="header">
        <h1 class="report-title">Budget Performance Report</h1>
        <div class="report-subtitle">
            Fiscal Year: {{ $settings->fiscal_year }} | Period: {{ $quarter_label }}
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value">₦{{ number_format($stats['revenue']['actual']) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Expenditure</div>
            <div class="stat-value">₦{{ number_format($stats['expenditure']['actual']) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Budget Utilization</div>
            <div class="stat-value">{{ $stats['expenditure']['percentage'] }}%</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Health Status</div>
            <div class="stat-value" style="font-size: 11pt">{{ $stats['expenditure']['label'] }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Subhead / MDA</th>
                <th class="text-right">Approved Budget</th>
                <th class="text-right">Actual Spent</th>
                <th class="text-right">Variance</th>
                <th style="text-align: center;">%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($performance as $item)
                <tr>
                    <td>
                        <strong>{{ $item->description }}</strong><br>
                        <small>{{ $item->mda_name }}</small>
                    </td>
                    <td class="text-right">₦{{ number_format($item->budget) }}</td>
                    <td class="text-right">₦{{ number_format($item->actual) }}</td>
                    <td class="text-right">₦{{ number_format($item->variance) }}</td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $item->status }}">
                            {{ $item->percentage }}%
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>