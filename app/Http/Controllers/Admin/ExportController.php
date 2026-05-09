<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

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
            // NEW: Direct filter for the quarter column
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

        $pdf = Pdf::loadView('pdf.expenditure-report', [
            'releases' => $releases,
            'total'    => $total,
            'date'     => now()->format('d/m/Y H:i'),
            'filters'  => $request->all()
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Expenditure_Report_'.now()->format('Y-m-d').'.pdf');
    }
}