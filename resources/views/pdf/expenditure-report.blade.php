<div style="font-family: sans-serif; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="text-transform: uppercase; margin: 0;">Katsina State Government</h2>
        <h3 style="margin: 5px 0; color: #064e3b;">Ministry of Budget & Economic Planning</h3>
        <p style="font-size: 12px; font-weight: bold;">Expenditure Release Report - Fiscal Year 2026</p>
        <hr>
    </div>

    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
        <thead>
            <tr style="background: #f1f5f9;">
                <th style="border: 1px solid #ddd; padding: 8px;">Date</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Ref No.</th>
                <th style="border: 1px solid #ddd; padding: 8px;">MDA Code</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Subhead</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Narration</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Amount (₦)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($releases as $release)
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;">{{ $release->release_date }}</td>
                <td style="border: 1px solid #ddd; padding: 8px;">{{ $release->reference_no }}</td>
                <td style="border: 1px solid #ddd; padding: 8px;">{{ $release->mda_code }}</td>
                <td style="border: 1px solid #ddd; padding: 8px;">{{ $release->subhead_code }}</td>
                <td style="border: 1px solid #ddd; padding: 8px;">{{ $release->narration }}</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">{{ number_format($release->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background: #f8fafc;">
                <td colspan="5" style="border: 1px solid #ddd; padding: 10px; text-align: right;">Total Approved Expenditure:</td>
                <td style="border: 1px solid #ddd; padding: 10px; text-align: right;">₦ {{ number_format($total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 50px; font-size: 10px; text-align: right;">
        <p>Generated on: {{ $date }}</p>
        <p style="font-style: italic;">This is a system-generated report from the Budget Control System.</p>
    </div>
</div>