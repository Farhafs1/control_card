<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use Livewire\Attributes\Computed;
use Gemini\Client; // Import the Gemini Client

class ComparativeAnalysis extends Component
{
    public $comparisonPeriods = [
        ['year' => 2025, 'quarter' => 'all'],
        ['year' => 2026, 'quarter' => 'all'],
    ];

    public $filter = 'all';
    public $aiInsight = "";
    public $isAnalyzing = false;
    public $showInsight = false; // New: Controls view toggle

    // UI Action: Add a new period column
    public function addPeriod()
    {
        if (count($this->comparisonPeriods) < 5) {
            $this->comparisonPeriods[] = ['year' => 2026, 'quarter' => 'all'];
        }
    }

    // UI Action: Remove a specific period column
    public function removePeriod($index)
    {
        if (count($this->comparisonPeriods) > 1) {
            unset($this->comparisonPeriods[$index]);
            $this->comparisonPeriods = array_values($this->comparisonPeriods);
        }
    }

    // Toggles back to the table view
    public function closeInsight()
    {
        $this->showInsight = false;
    }

    #[Computed]
    public function currentYear()
    {
        return Setting::first()?->fiscal_year ?? 2026;
    }

    #[Computed]
    public function results()
    {
        return $this->getAggregateData();
    }

    /**
     * Core Logic: Aggregates totals based on the dropdown filter
     */
    public function getAggregateData(): Collection
    {
        $categoryMap = [
            'Personnel' => ['prefix' => '21%', 'length' => 8],
            'Overhead'  => ['prefix' => '22%', 'length' => 8],
            'Capital'   => ['prefix' => null,  'length' => 10],
            'Revenue'   => ['prefix' => '1%',  'length' => 8],
        ];

        $categoriesToProcess = match($this->filter) {
            'all_expenditure' => ['Personnel', 'Overhead', 'Capital'],
            'Revenue'         => ['Revenue'],
            'Personnel'       => ['Personnel'],
            'Overhead'        => ['Overhead'],
            'Capital'         => ['Capital'],
            default           => ['Personnel', 'Overhead', 'Capital', 'Revenue'],
        };

        $results = [];

        foreach ($categoriesToProcess as $catName) {
            $config = $categoryMap[$catName];
            $targetYear = collect($this->comparisonPeriods)->last()['year'];

            $provisionQuery = DB::table('subheads')->where('fiscal_year', $targetYear);
            
            if ($config['prefix']) {
                $provisionQuery->where('subhead_code', 'like', $config['prefix']);
            }
            $provisionQuery->whereRaw("LENGTH(subhead_code) = {$config['length']}");

            $totalProvision = $provisionQuery->selectRaw('
                SUM(
                    COALESCE(approved_provision, 0) + 
                    COALESCE(virement_provision, 0) + 
                    COALESCE(supplementary_provision, 0) + 
                    COALESCE(additional_provision, 0)
                ) as total
            ')->value('total') ?? 0;

            $periodValues = [];
            foreach ($this->comparisonPeriods as $period) {
                $query = DB::table('releases')
                    ->join('subheads', 'releases.subhead_id', '=', 'subheads.id')
                    ->where('releases.year', $period['year']);

                if ($period['quarter'] !== 'all') {
                    $query->where('releases.quarter', $period['quarter']);
                }

                $query->where(function ($q) use ($config) {
                    if ($config['prefix']) {
                        $q->where('subheads.subhead_code', 'like', $config['prefix']);
                    }
                    $q->whereRaw("LENGTH(subheads.subhead_code) = {$config['length']}");
                });

                $periodValues[] = $query->sum('releases.amount') ?? 0;
            }

            $val1 = $periodValues[0] ?? 0;
            $val2 = end($periodValues) ?: 0;
            $diff = $val2 - $val1;
            $percentage = $val1 > 0 ? ($diff / $val1) * 100 : 0;

            $results[] = (object)[
                'category'            => $catName,
                'total_provision'     => $totalProvision,
                'values'              => $periodValues,
                'total_variance'      => $diff,
                'variance_percentage' => $percentage,
            ];
        }

        return collect($results);
    }

    /**
     * Adopted AI Logic: Professional Briefing Format
     */
    public function generateAiReport()
    {
        $this->showInsight = true; // Switch view
        $this->isAnalyzing = true;
        $this->aiInsight = ""; 
        
        set_time_limit(150); 

        $data = $this->results(); // Use computed property

        if ($data->isEmpty()) {
            $this->aiInsight = "Insufficient data to perform comparative analysis.";
            $this->isAnalyzing = false;
            return;
        }

        // Prepare professional data summary
        $summary = "FISCAL COMPARISON SUMMARY (" . count($this->comparisonPeriods) . " Periods):\n";
        foreach ($data as $item) {
            $trend = collect($item->values)->map(fn($v) => "₦".number_format($v, 2))->implode(' -> ');
            $summary .= "- {$item->category}: Trend [{$trend}] | Variance: ₦" . number_format($item->total_variance, 2) . " ({$item->variance_percentage}%)\n";
        }

        try {
            $prompt = "You are the Chief Budget Officer for Katsina State. 
            Analyze this comparative expenditure and revenue summary:
            
            $summary
            
            Provide a professional 5-paragraph executive brief with specific headings:
            1. FISCAL TRAJECTORY: How are the categories evolving across the selected periods?
            2. REVENUE VS EXPENDITURE BALANCE: Comment on the sustainability based on the trends.
            3. STRATEGIC RECOMMENDATION: One actionable directive for the Governor to optimize fiscal performance.
            
            Keep the tone authoritative, professional, and tailored for a high-level government briefing.";

            // Use the client logic from your reference file
            $client = app(Client::class);
            $result = $client->generativeModel('gemini-2.5-flash') // or 'gemini-1.5-flash'
                             ->generateContent($prompt);

            $this->aiInsight = $result->text();
            
        } catch (\Exception $e) {
            \Log::error("Gemini Comparative Error: " . $e->getMessage());
            $this->aiInsight = "The AI Financial Consultant is currently offline. Please try again in a moment.";
        }

        $this->isAnalyzing = false;
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['filter', 'comparisonPeriods'])) {
            // If the user changes filters while viewing insight, refresh the report
            if ($this->showInsight) {
                $this->generateAiReport();
            }
        }
    }

    public function render()
    {
        return view('livewire.comparative-analysis');
    }
}