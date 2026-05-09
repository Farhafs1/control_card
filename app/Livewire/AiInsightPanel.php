<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Gemini\Client; // Using the Client service we established

class AiInsightPanel extends Component
{
    public $insight = "";
    public $loading = false;

    #[On('data-updated')]
    public function analyzeData($comparisonData)
    {
        $this->loading = true;
        $this->insight = "";

        // 1. Contextual Data Preparation
        $collection = collect($comparisonData);
        
        // Identify key fiscal drivers
        $topGrown = $collection->sortByDesc('variance_percent')->take(3);
        $topDropped = $collection->sortBy('variance_percent')->take(3);
        $highestVolume = $collection->sortByDesc('net_variance')->first();

        // Build a structured data string for the AI
        $stats = [
            "Gainers" => $topGrown->map(fn($m) => "{$m['name']} (+{$m['variance_percent']}%)")->implode(', '),
            "Decliners" => $topDropped->map(fn($m) => "{$m['name']} ({$m['variance_percent']}%)")->implode(', '),
            "Heavy_Hitter" => "{$highestVolume['name']} with a net change of ₦" . number_format($highestVolume['net_variance'], 2)
        ];

        // 2. The Multi-Role Prompt
        $prompt = "
        Act as a Senior Fiscal Consultant and Chief Budget Officer. 
        Analyze this budgetary variance data:
        - TOP GROWTH: {$stats['Gainers']}
        - TOP DECLINES: {$stats['Decliners']}
        - LARGEST VOLUME IMPACT: {$stats['Heavy_Hitter']}

        Your task: Provide a deep-dive comparison for both Executive leadership and Financial Analysts.

        STRUCTURE YOUR RESPONSE IN MARKDOWN:
        ### 1. CRITICAL VARIANCE ANALYSIS
        * Use 2-3 bullet points to explain the 'Why' behind these shifts. 
        * Differentiate between nominal percentage changes and actual fiscal weight.

        ### 2. FINANCIAL RISK & EFFICIENCY
        * Identify if these trends suggest aggressive implementation or potential budget leakage.
        * Mention if any variance exceeds standard public sector thresholds (10-15%).

        ### 3. ANALYST RECOMMENDATION
        * Provide one technical directive for internal auditors and one strategic directive for the Hon. Commissioner.

        STRICT RULES:
        - Use Markdown (### for headers).
        - No fluff. Professional, forensic, and data-driven tone.
        - Keep sentences punchy.";

        // 3. Execution
        try {
            $client = app(Client::class);
            $result = $client->generativeModel('gemini-2.0-flash')
                             ->generateContent($prompt);
            
            $this->insight = $result->text();
        } catch (\Exception $e) {
            \Log::error("AI Insight Error: " . $e->getMessage());
            $this->insight = "Analysis temporarily unavailable. Please verify API configuration.";
        }
        
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.ai-insight-panel');
    }
}