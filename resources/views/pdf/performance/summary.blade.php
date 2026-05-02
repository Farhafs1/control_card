<!DOCTYPE html>
<html>
<head>
    <title>MDA Performance Summary</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background-color: #064e3b; color: white; }
        .header { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h2>KATSINA STATE GOVERNMENT</h2>
        <h3>MDA Budget Performance Summary - Quarter {{ $quarter }}</h3>
    </div>

    <table>
        <thead>
            <tr>
                <th>MDA Name</th>
                <th>Total Approved</th>
                <th>Total Released</th>
                <th>Performance %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results as $mda)
                <tr>
                    <td>{{ $mda['mda'] }}</td>
                    <td class="text-right">{{ number_format($mda['total_budget'], 2) }}</td>
                    <td class="text-right">{{ number_format($mda['total_releases'], 2) }}</td>
                    <td class="text-right"><strong>{{ number_format($mda['performance'], 1) }}%</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>