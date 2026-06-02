<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    public function expenditure(Request $request)
    {
        $query = Release::with('subhead.mda')
            ->when($request->search, function($q) use ($request) {
                $q->where(function($sub) use ($request) {
                    $sub->where('reference_no', 'like', "%{$request->search}%")
                        ->orWhere('mda_code', 'like', "%{$request->search}%")
                        ->orWhere('subhead_code', 'like', "%{$request->search}%")
                        ->orWhere('narration', 'like', "%{$request->search}%");
                });
            })
            // Direct filter for the quarter column
            ->when($request->quarter, function($q) use ($request) {
                return $q->where('quarter', $request->quarter);
            })
            // Existing date filters
            ->when($request->dateFrom, fn($q) => $q->whereDate('release_date', '>=', $request->dateFrom))
            ->when($request->dateTo, fn($q) => $q->whereDate('release_date', '<=', $request->dateTo))
            ->when($request->minAmount, fn($q) => $q->where('amount', '>=', $request->minAmount))
            ->when($request->status && $request->status !== 'all', function($q) use ($request) {
                $q->where('is_cancelled', $request->status === 'cancelled');
            });

        $releases = $query->latest('release_date')->get();
        $total = $releases->where('is_cancelled', false)->sum('amount');

        // ----------------------------------------------------
        // DYNAMIC FILENAME FORMATTING STRATEGY
        // ----------------------------------------------------
        // Determine Year from date parameters or default to current year
        $year = now()->format('Y');
        if ($request->dateFrom) {
            $year = \Carbon\Carbon::parse($request->dateFrom)->format('Y');
        }

        // Determine Quarter tracking labels
        $quarterLabel = 'Full-Year';
        if ($request->quarter) {
            $quarterLabel = 'Q' . $request->quarter;
        }

        // Output format structure: Expenditure_Report_2026_Q1.pdf
        $fileName = sprintf(
            'Expenditure_Report_%s_%s.pdf',
            $year,
            $quarterLabel
        );
        // ----------------------------------------------------

        $pdf = Pdf::loadView('pdf.expenditure-report', [
            'releases' => $releases,
            'total'    => $total,
            'date'     => now()->format('d/m/Y H:i'),
            'filters'  => $request->all()
        ])->setPaper('a4', 'landscape');

        // Return PDF download action initialized with the formatted filename string
        return $pdf->download($fileName);
    }
}