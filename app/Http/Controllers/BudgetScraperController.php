<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BudgetScraperController extends Controller
{
    /**
     * Trigger the scraper command to run in a background process
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'limit' => 'nullable|integer|min=0|max=1000',
                'from' => 'nullable|date|date_format:Y-m-d',
                'to' => 'nullable|date|date_format:Y-m-d|after_or_equal:from',
                'headless' => 'nullable|boolean',
                'batch_size' => 'nullable|integer|min=1|max=200'
            ]);

            $limit = $request->input('limit', 0);
            $from = $request->input('from', '2026-01-01');
            $to = $request->input('to', '2026-01-31');
            $headless = $request->input('headless', true);
            $batchSize = $request->input('batch_size', 50);

            // Check if a scrape is already running
            $existingProgress = Cache::get('scrape_progress');
            if ($existingProgress && isset($existingProgress['percent']) && $existingProgress['percent'] > 0 && $existingProgress['percent'] < 100) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A scraping process is already running. Please wait for it to complete.',
                    'current_progress' => $existingProgress['percent']
                ], 409);
            }

            // Clear any old progress data
            Cache::forget('scrape_progress');
            Cache::forget('scrape_metrics_last_run');

            // Initialize progress cache with proper structure matching the scraper
            Cache::put('scrape_progress', [
                'percent' => 0,
                'status' => 'Initializing scraper...',
                'metrics' => [
                    'processed' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'failed' => 0
                ],
                'updated_at' => now()->toDateTimeString()
            ], 600);

            // Build command options
            $commandOptions = [
                '--limit' => $limit,
                '--from' => $from,
                '--to' => $to,
                '--headless' => filter_var($headless, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
                '--batch-size' => $batchSize
            ];

            // Dispatch to queue (non-blocking)
            Artisan::queue('budget:scrape', $commandOptions);

            Log::info('Budget scraper dispatched', [
                'options' => $commandOptions,
                'user' => auth()->user()?->id ?? 'cli'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Scraper command dispatched successfully to background queue.',
                'options' => $commandOptions
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch scraper', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start scraper: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current scraping progress for live dashboard updates
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function progress()
    {
        try {
            // Get current progress (matches scraper's cache structure)
            $progress = Cache::get('scrape_progress', [
                'percent' => 0,
                'status' => 'No active scraping session',
                'metrics' => [
                    'processed' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'failed' => 0
                ],
                'updated_at' => null
            ]);

            // Get last run metrics if available
            $lastRun = Cache::get('scrape_metrics_last_run', null);

            // Determine if scraper is active
            $isActive = Cache::has('scrape_progress') && 
                        isset($progress['percent']) && 
                        $progress['percent'] > 0 && 
                        $progress['percent'] < 100;

            // Add additional metadata for dashboard
            $response = [
                'success' => true,
                'is_active' => $isActive,
                'progress' => $progress,
                'last_run' => $lastRun,
                'timestamp' => now()->toDateTimeString()
            ];

            // Add console-style logs for debugging (optional)
            if ($isActive && isset($progress['status'])) {
                $response['current_operation'] = $progress['status'];
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Failed to fetch scrape progress', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch progress: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed metrics from the last completed scrape
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function lastRunMetrics()
    {
        try {
            $lastRun = Cache::get('scrape_metrics_last_run', null);

            if (!$lastRun) {
                return response()->json([
                    'success' => true,
                    'has_data' => false,
                    'message' => 'No completed scrape runs found'
                ]);
            }

            return response()->json([
                'success' => true,
                'has_data' => true,
                'metrics' => $lastRun
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop/kill a running scrape process (advanced)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function stop()
    {
        try {
            $progress = Cache::get('scrape_progress');
            
            if (!$progress || (isset($progress['percent']) && $progress['percent'] >= 100)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active scraping process found'
                ], 404);
            }

            // Clear cache to signal stop
            Cache::forget('scrape_progress');
            
            // Note: This doesn't actually kill the Artisan process
            // It just clears the UI tracking. The actual process will continue
            // but won't be visible to the dashboard.
            
            Log::warning('Scrape progress manually cleared', [
                'user' => auth()->user()?->id ?? 'unknown',
                'progress_at_stop' => $progress['percent'] ?? 0
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Scrape tracking cleared. Note: The actual process may continue running.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to stop scraper: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get queue status for the scraper job
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function queueStatus()
    {
        try {
            // This requires Laravel Horizon or similar queue monitoring
            // Simple version - check if any pending jobs exist
            $queueSize = 0;
            
            if (method_exists(\Queue::class, 'size')) {
                $queueSize = \Queue::size('default');
            }
            
            $isRunning = Cache::has('scrape_progress') && 
                        Cache::get('scrape_progress')['percent'] > 0 && 
                        Cache::get('scrape_progress')['percent'] < 100;
            
            return response()->json([
                'success' => true,
                'queue_size' => $queueSize,
                'scraper_running' => $isRunning,
                'timestamp' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get queue status'
            ], 500);
        }
    }

    /**
     * Get dashboard summary with all relevant data
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard()
    {
        try {
            $progress = Cache::get('scrape_progress', [
                'percent' => 0,
                'status' => 'No active session',
                'metrics' => ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0],
                'updated_at' => null
            ]);
            
            $lastRun = Cache::get('scrape_metrics_last_run', null);
            $isActive = $progress['percent'] > 0 && $progress['percent'] < 100;
            
            // Calculate summary statistics
            $totalStaged = \App\Models\ScrapedRelease::count();
            $totalApproved = \App\Models\Release::count();
            $pendingApproval = \App\Models\ScrapedRelease::where('status', 'circulating')->count();
            
            return response()->json([
                'success' => true,
                'active_scrape' => [
                    'is_running' => $isActive,
                    'progress' => $progress['percent'],
                    'current_status' => $progress['status'],
                    'metrics' => $progress['metrics'],
                    'last_update' => $progress['updated_at']
                ],
                'last_completed_scrape' => $lastRun,
                'database_summary' => [
                    'total_staged_records' => $totalStaged,
                    'total_approved_records' => $totalApproved,
                    'pending_approval' => $pendingApproval
                ],
                'timestamp' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }
}