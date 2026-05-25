<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ScrapedRelease;
use App\Models\Release;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DataExtraction extends Component
{
    // Form Variables
    public $batchLimit = 50;
    public $fromDate = null;
    public $toDate = null;
    public $headlessMode = true;
    
    // View Mechanics
    public $isCrawling = false;
    public $consoleProgress = 0;
    public $consoleLogs = [];
    public $currentMetrics = [
        'processed' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'failed' => 0
    ];
    
    // Internal tracking
    private $lastLogCount = 0;
    private $lastProgress = 0;

    protected $listeners = ['refreshStatus' => 'checkScrapeStatus'];

    public function mount()
    {
        // Set default dates (last 7 days)
        $this->fromDate = now()->subDays(7)->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
        
        // Check if a scrape was running before page reload
        $this->checkScrapeStatus();
    }

    /**
     * Polls the system cache to read real-time progress from the background scraper.
     */
    public function checkScrapeStatus()
    {
        $progressData = Cache::get('scrape_progress');
        $isRunningFlag = Cache::get('scraper_running', false);
        
        // Case 1: Scraper is actively running (has progress data OR running flag)
        if ($progressData || $isRunningFlag) {
            $this->isCrawling = true;
            
            // Update progress if we have data
            if ($progressData) {
                $newProgress = $progressData['percent'] ?? 0;
                
                // Only update if progress changed
                if ($newProgress != $this->lastProgress) {
                    $this->consoleProgress = $newProgress;
                    $this->lastProgress = $newProgress;
                }
                
                // Update metrics
                if (isset($progressData['metrics'])) {
                    $this->currentMetrics = $progressData['metrics'];
                }
                
                // Add new logs only (avoid duplicates)
                $newLogs = [];
                if (isset($progressData['status']) && !empty($progressData['status'])) {
                    $statusLog = $progressData['status'];
                    // Check if this status already exists in logs
                    if (!in_array($statusLog, $this->consoleLogs)) {
                        $newLogs[] = $statusLog;
                    }
                }
                
                // Add metrics log (only if metrics changed)
                if (isset($progressData['metrics']) && !empty($progressData['metrics'])) {
                    $metrics = $progressData['metrics'];
                    $metricsLog = "[METRICS] Processed: {$metrics['processed']} | Created: {$metrics['created']} | Updated: {$metrics['updated']} | Skipped: {$metrics['skipped']} | Failed: {$metrics['failed']}";
                    
                    // Only add if this exact metrics log isn't the last one
                    $lastLog = end($this->consoleLogs);
                    if ($lastLog !== $metricsLog && !in_array($metricsLog, $this->consoleLogs)) {
                        $newLogs[] = $metricsLog;
                    }
                }
                
                // Add timestamp log (only once per minute)
                if (isset($progressData['updated_at'])) {
                    $timestampLog = "[SYSTEM] Last update: {$progressData['updated_at']}";
                    $shouldAdd = true;
                    // Check if we already have a timestamp from the last minute
                    foreach ($this->consoleLogs as $existingLog) {
                        if (str_contains($existingLog, 'Last update:') && 
                            str_contains($existingLog, substr($progressData['updated_at'], 0, 16))) {
                            $shouldAdd = false;
                            break;
                        }
                    }
                    if ($shouldAdd) {
                        $newLogs[] = $timestampLog;
                    }
                }
                
                // Add new logs
                foreach ($newLogs as $log) {
                    $this->consoleLogs[] = $log;
                }
                
                // Keep last 200 logs
                if (count($this->consoleLogs) > 200) {
                    $this->consoleLogs = array_slice($this->consoleLogs, -200);
                }
                
                $this->dispatch('log-updated');
            }
            
            // Check if scraping is actually complete (progress 100 AND no cache)
            if ($progressData && $this->consoleProgress >= 100) {
                // Don't complete yet - let the scraper clear its own cache
                // We'll detect completion in the next poll when cache is gone
            }
            
            return;
        }
        
        // Case 2: No active scraper
        if ($this->isCrawling) {
            // We were crawling but now no cache - scraping must be complete
            $this->completeScraping();
        } else {
            // Not crawling and no cache - ensure UI is reset
            $this->isCrawling = false;
            $this->consoleProgress = 0;
        }
    }

    /**
     * Dispatches the scraper tool asynchronously to background.
     */
    public function startExtraction()
    {
        $this->validate([
            'batchLimit' => 'required|integer|min:1|max:500',
            'fromDate' => 'nullable|date',
            'toDate' => 'nullable|date|after_or_equal:fromDate',
            'headlessMode' => 'boolean'
        ]);

        // Check if a scrape is already running
        if (Cache::has('scrape_progress') || Cache::get('scraper_running', false)) {
            $this->dispatch('swal', [
                'title' => 'Scraper Already Running',
                'text'  => 'A scraping process is already in progress. Please wait for it to complete.',
                'icon'  => 'warning'
            ]);
            return;
        }

        // Clear old data
        Cache::forget('scrape_progress');
        Cache::forget('scrape_metrics_last_run');
        Cache::forget('scraper_running');
        
        // Reset UI state
        $this->isCrawling = true;
        $this->consoleProgress = 0;
        $this->consoleLogs = [];
        $this->currentMetrics = ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        $this->lastProgress = 0;
        $this->lastLogCount = 0;
        
        // Add initialization log
        $initLog = "[SYSTEM] Initializing extraction engine for {$this->batchLimit} records...";
        $this->consoleLogs[] = $initLog;
        
        // Initialize cache with proper structure
        Cache::put('scrape_progress', [
            'percent' => 0,
            'status' => $initLog,
            'metrics' => $this->currentMetrics,
            'updated_at' => now()->toDateTimeString()
        ], 600);
        
        // Set running flag
        Cache::put('scraper_running', true, 600);
        
        $this->dispatch('log-updated');

        // Build command
        $artisanPath = base_path('artisan');
        $commandOptions = [
            '--limit' => $this->batchLimit,
            '--headless' => $this->headlessMode ? 'true' : 'false'
        ];
        
        if ($this->fromDate && $this->toDate) {
            $commandOptions['--from'] = $this->fromDate;
            $commandOptions['--to'] = $this->toDate;
        }
        
        $optionsString = '';
        foreach ($commandOptions as $key => $value) {
            $optionsString .= " {$key}={$value}";
        }
        
        // Dispatch asynchronously
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $commandStr = "php \"{$artisanPath}\" budget:scrape{$optionsString} > NUL 2>&1";
            pclose(popen("start /B cmd /C \"{$commandStr}\"", "r"));
        } else {
            $commandStr = "php \"{$artisanPath}\" budget:scrape{$optionsString} > /dev/null 2>&1 &";
            exec($commandStr);
        }
        
        Log::info('Scraper dispatched', ['options' => $commandOptions]);
        
        $this->dispatch('swal', [
            'title' => 'Extraction Started',
            'text'  => 'The extraction engine is now running in the background.',
            'icon'  => 'success'
        ]);
    }

    /**
     * Handle scraping completion
     */
    private function completeScraping()
    {
        // Get final metrics if available
        $finalMetrics = Cache::get('scrape_metrics_last_run');
        
        if ($finalMetrics) {
            $this->consoleLogs[] = "[SYSTEM] " . str_repeat('=', 50);
            $this->consoleLogs[] = "[SUCCESS] FINAL METRICS:";
            $this->consoleLogs[] = "[SUCCESS] Duration: {$finalMetrics['duration_seconds']} seconds";
            $this->consoleLogs[] = "[SUCCESS] Records Processed: {$finalMetrics['records_processed']}";
            $this->consoleLogs[] = "[SUCCESS] Records Created: {$finalMetrics['records_created']}";
            $this->consoleLogs[] = "[SUCCESS] Records Updated: {$finalMetrics['records_updated']}";
            $this->consoleLogs[] = "[SUCCESS] Records Skipped: {$finalMetrics['records_skipped']}";
            $this->consoleLogs[] = "[SUCCESS] Records Failed: {$finalMetrics['records_failed']}";
            $this->consoleLogs[] = "[SUCCESS] Bulk Documents: {$finalMetrics['bulk_documents']}";
            $this->consoleLogs[] = "[SUCCESS] Standard Documents: {$finalMetrics['standard_documents']}";
            $this->consoleLogs[] = "[SYSTEM] " . str_repeat('=', 50);
        }
        
        $this->consoleLogs[] = "[SUCCESS] Scraping completed successfully!";
        $this->consoleLogs[] = "[SYSTEM] Staging environment has been safely refreshed.";
        
        $this->isCrawling = false;
        $this->consoleProgress = 100;
        
        $this->dispatch('log-updated');
        $this->dispatch('swal', [
            'title' => 'Process Complete',
            'text'  => 'Staging environment has been safely refreshed.',
            'icon'  => 'success'
        ]);
        
        // Clean up cache
        Cache::forget('scrape_progress');
        Cache::forget('scraper_running');
        // Keep metrics for a while (already stored with 86400 TTL)
    }

    /**
     * Stop the scraping process
     */
    public function killEngine()
    {
        Cache::forget('scrape_progress');
        Cache::forget('scraper_running');
        Cache::forget('scrape_metrics_last_run');
        
        $this->isCrawling = false;
        $this->consoleProgress = 0;
        $this->consoleLogs[] = "[WARN] Engine terminated by user request.";
        
        $this->dispatch('log-updated');
        $this->dispatch('swal', [
            'title' => 'Engine Stopped',
            'text'  => 'Termination signal sent successfully.',
            'icon'  => 'warning'
        ]);
    }

    /**
     * Clear console logs
     */
    public function clearLogs()
    {
        $this->consoleLogs = [];
        $this->dispatch('log-updated');
    }

    /**
     * Manual refresh of status
     */
    public function refreshStatus()
    {
        $this->checkScrapeStatus();
    }

    public function render()
    {
        return view('livewire.admin.data-extraction', [
            'stagedCount' => ScrapedRelease::count(),
            'totalPermanent' => Release::count(),
            'lastSync' => ScrapedRelease::latest()->first()?->created_at?->diffForHumans() ?? 'Never'
        ])->layout('layouts.app');
    }
}