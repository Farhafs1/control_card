<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScrapedRelease;
use App\Models\Release;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ScrapeExpenditure extends Command
{
    protected $signature = 'budget:scrape 
                            {--limit=0 : Maximum number of records to process (0 = no limit)}
                            {--from= : Start date for manual scrape (Y-m-d)}
                            {--to= : End date for manual scrape (Y-m-d)}
                            {--headless=false : Run browser in headless mode}
                            {--timeout=300 : Default timeout in seconds}
                            {--batch-size=50 : Number of records to process before memory cleanup}';

    protected $description = 'Scraper for Katsina State E-Budget Portal expenditure data';

    private $driver = null;
    private $metrics = [];
    private $processedRefs = []; // CRITICAL: Track processed references to prevent infinite loop
    private $logBuffer = []; // Buffer logs for batch writing to cache

    // Your proven timing constants
    private const PORTAL_URL = 'https://kteb.katsinastate.gov.ng/release-collection';
    private const CHROME_DRIVER_URL = 'http://127.0.0.1:9515';
    private const MAX_RETRIES = 5;
    private const RETRY_DELAY_MS = 3000;
    private const RATE_LIMIT_ROWS = 100;
    private const RATE_LIMIT_DELAY_US = 500000;
    private const TABLE_STABILIZE_SECONDS = 5;
    private const PREVIEW_DOCUMENT_WAIT_SECONDS = 4;
    private const PROGRESS_CACHE_TTL_SECONDS = 600;

    public function handle(): int
    {
        // Initialize cache with proper structure including logs array
        Cache::put('scrape_progress', [
            'percent' => 0,
            'status' => '[SYSTEM] Scraper boot sequence initialized...',
            'metrics' => [
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0
            ],
            'logs' => ['[SYSTEM] Scraper boot sequence initialized...'],
            'updated_at' => now()->toDateTimeString()
        ], self::PROGRESS_CACHE_TTL_SECONDS);
        
        $this->initializeMetrics();
        
        // At the top of handle() after $this->initializeMetrics();
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function() {
                $this->logToCache("⚠️ Received interrupt signal, cleaning up...", 'warn');
                $this->completeScraping();
                exit(0);
            });
        }

        // After Cache::put('scrape_progress', ...)
        Cache::put('scraper_running', true, self::PROGRESS_CACHE_TTL_SECONDS);
        
        try {
            $this->validateOptions();
            
            $limit = (int) $this->option('limit');
            $isHeadless = filter_var($this->option('headless'), FILTER_VALIDATE_BOOLEAN);
            $timeout = (int) $this->option('timeout');
            $batchSize = (int) $this->option('batch-size');
            $user = 'yadamm';
            $pass = 'Adamm@86';
            
            list($startLimit, $endLimit, $isManual) = $this->determineDateRange();
            
            // Write initial progress to cache
            $this->writeToCache(0, '[SYSTEM] Scraper boot sequence initialized...');
            
            $this->info('📊 ' . str_repeat('=', 60));
            $this->info('STARTING PRODUCTION SCRAPER');
            $this->info('📊 ' . str_repeat('=', 60));
            $this->info("Mode: " . ($isManual ? "Manual Range" : "Automatic Weekly"));
            $this->info("Date Range: {$startLimit->toDateString()} → {$endLimit->toDateString()}");
            $this->info("Headless Mode: " . ($isHeadless ? "Yes" : "No"));
            $this->info("Batch Size: {$batchSize}");
            $this->info('');
            
            $this->logToCache('[SYSTEM] Initializing scraper engine...');
            
            // Connect and configure WebDriver
            $this->connectDriver($isHeadless, $timeout);

            // --- 1. LOGIN (Existing functional logic) ---
            $this->info("Step 1: Navigating to Login...");
            $this->driver->get('https://kteb.katsinastate.gov.ng/login');
            $this->info("Page title is: " . $this->driver->getTitle());
            
            $this->driver->wait(40)->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name('username'))
            );
            
            $this->info("Entering credentials...");
            $this->driver->findElement(WebDriverBy::name('username'))->sendKeys($user);
            $this->driver->findElement(WebDriverBy::name('password'))->sendKeys($pass);
            
            sleep(15); 

            $this->info("Clicking Sign in...");
            try {
                $loginBtn = $this->driver->findElement(WebDriverBy::xpath("//button[contains(., 'Sign in')]"));
                $loginBtn->click();
            } catch (\Exception $e) {
                $this->warn("Button click failed, trying ENTER key fallback...");
                $this->driver->findElement(WebDriverBy::name('password'))->sendKeys([WebDriverKeys::ENTER]);
            }
            
            // --- 2. NAVIGATION (Existing functional logic) ---
            $this->info("Step 2: Starting Navigation...");

            $parentMenu = $this->driver->wait(15)->until(
                WebDriverExpectedCondition::elementToBeClickable(
                    WebDriverBy::xpath("(//li[contains(@class, 'link_item') and contains(., 'Release Processing')])[1]")
                )
            );
            $parentMenu->click();

            $this->info("Waiting for sub-menu expansion...");
            sleep(5); 

            $this->info("Clicking the sub-menu collection link via JS...");
            
            try {
                $xpath = "(//span[contains(@class, 'MuiListItemText-primary') and text()='Release Processing'])[last()]";
                $this->driver->wait(10)->until(
                    WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::xpath($xpath))
                );
                $subMenuItem = $this->driver->findElement(WebDriverBy::xpath($xpath));
                $this->driver->executeScript("arguments[0].click();", [$subMenuItem]);
            } catch (\Exception $e) {
                $this->error("Sub-menu click failed: " . $e->getMessage());
                $this->driver->executeScript(
                    "document.evaluate(\"(//li[contains(., 'Release Processing')])[last()]\", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.click();"
                );
            }
            
            // Navigate to portal
            $this->logToCache('🌐 Navigating Release Collection Table...');
            $this->info('🌐 Navigating to Release Collection Table...');
            // $this->driver->get(self::PORTAL_URL);
            
            // Wait for and validate table (YOUR PROVEN WAIT LOGIC)
            $this->waitForTable();
            $this->validateTableSchema();
            
            if (!$isHeadless) {
                $this->warn('⚠️  Manual mode active. Please set your filters in Chrome.');
                $this->confirm('Press Enter when the table is ready for capture', true);
            }

            $this->info("🛑 PAUSED: Set your filters in Chrome.");
            $this->confirm("Press Enter when the table is ready", true);
            
            sleep(2);
            
            // Capture table snapshot ONCE (prevents infinite loop)
            $referenceChecklist = $this->captureTableSnapshot($startLimit, $endLimit, $isManual);
            
            // Apply limit if specified
            if ($limit > 0 && count($referenceChecklist) > $limit) {
                $referenceChecklist = array_slice($referenceChecklist, 0, $limit);
                $this->warn("⚠️  Limited to first {$limit} records");
            }
            
            $total = count($referenceChecklist);
            $this->logToCache("[SYSTEM] Table snapshot complete. {$total} records queued for processing.");
            $this->info("📸 Table snapshot complete. {$total} records queued for processing.");
            
            if ($total === 0) {
                $this->warn('No records found in the specified date range.');
                return 0;
            }
            
            // Process each record ONCE (no infinite loop)
            $this->processedRefs = [];
            
            foreach ($referenceChecklist as $index => $item) {
                $globalIndex = $index + 1;
                $this->processRecordWithRetry($item, $globalIndex, $total);
                
                // Periodic memory cleanup
                if ($globalIndex % 20 === 0) {
                    $this->cleanupMemory();
                }
                
                // Add delay between records to avoid overwhelming the server
                if ($globalIndex < $total) {
                    sleep(2);
                }
            }
            
            $this->completeScraping();
            $this->logFinalMetrics();
            
            return 0;
            
        } catch (\Exception $e) {
            $this->handleCriticalFailure($e);
            return 1;
        } finally {
            $this->disconnectDriver();
            Cache::forget('scrape_progress');
            Cache::forget('scraper_running');
        }
    }

    /**
     * Write status to cache for Livewire UI
     */
    private function writeToCache(int $percent, string $status): void
    {
        try {
            // Get existing progress to preserve logs
            $existing = Cache::get('scrape_progress', []);
            
            $progress = [
                'percent' => min(100, $percent),
                'status' => $status,
                'metrics' => $this->getCurrentMetrics(),
                'updated_at' => now()->toDateTimeString(),
                'logs' => $existing['logs'] ?? [] // Preserve existing logs
            ];
            
            Cache::put('scrape_progress', $progress, self::PROGRESS_CACHE_TTL_SECONDS);
        } catch (\Exception $e) {
            // Non-critical
        }
    }

    /**
     * Log message to both console AND cache for UI
     */
    private function logToCache(string $message, string $type = 'info'): void
    {
        // Output to console
        match($type) {
            'error' => $this->error($message),
            'warn' => $this->warn($message),
            'success' => $this->info("✅ {$message}"),
            default => $this->info($message)
        };
        
        // Add to buffer for batch writing
        $this->logBuffer[] = $message;
        
        // Write to cache every 5 logs or immediately for important messages
        if (count($this->logBuffer) >= 5 || in_array($type, ['error', 'success'])) {
            $this->flushLogBuffer();
        }
    }

    /**
     * Flush log buffer to cache
     */
    private function flushLogBuffer(): void
    {
        if (empty($this->logBuffer)) {
            return;
        }
        
        try {
            // Get existing progress
            $progress = Cache::get('scrape_progress', [
                'percent' => 0,
                'status' => 'Initializing...',
                'metrics' => $this->getCurrentMetrics(),
                'logs' => [],
                'updated_at' => now()->toDateTimeString()
            ]);
            
            // Initialize logs array if not exists
            if (!isset($progress['logs'])) {
                $progress['logs'] = [];
            }
            
            // Add buffered logs
            foreach ($this->logBuffer as $log) {
                $progress['logs'][] = $log;
            }
            
            // Keep last 200 logs
            if (count($progress['logs']) > 200) {
                $progress['logs'] = array_slice($progress['logs'], -200);
            }
            
            // Update other fields
            $progress['updated_at'] = now()->toDateTimeString();
            $progress['metrics'] = $this->getCurrentMetrics();
            $progress['percent'] = $this->getCurrentMetrics()['processed'] > 0 ? 
                min(95, round(($this->getCurrentMetrics()['processed'] / $this->metrics['total_records']) * 100)) : 
                $progress['percent'];
            
            Cache::put('scrape_progress', $progress, self::PROGRESS_CACHE_TTL_SECONDS);
            
            // Clear buffer
            $this->logBuffer = [];
            
        } catch (\Exception $e) {
            // Non-critical
        }
    }

    /**
     * Add a single log entry directly (for important messages)
     */
    private function addLog(string $message, string $type = 'info'): void
    {
        // Output to console
        match($type) {
            'error' => $this->error($message),
            'warn' => $this->warn($message),
            'success' => $this->info("✅ {$message}"),
            default => $this->info($message)
        };
        
        try {
            $progress = Cache::get('scrape_progress', [
                'percent' => 0,
                'status' => 'Initializing...',
                'metrics' => $this->getCurrentMetrics(),
                'logs' => [],
                'updated_at' => now()->toDateTimeString()
            ]);
            
            if (!isset($progress['logs'])) {
                $progress['logs'] = [];
            }
            
            // Add prefix
            $prefix = match($type) {
                'error' => '[ERROR] ',
                'success' => '[SUCCESS] ',
                'warn' => '[WARN] ',
                default => '--- '
            };
            
            $fullLog = $prefix . $message;
            
            if (!in_array($fullLog, $progress['logs'])) {
                $progress['logs'][] = $fullLog;
            }
            
            // Keep last 200 logs
            if (count($progress['logs']) > 200) {
                $progress['logs'] = array_slice($progress['logs'], -200);
            }
            
            $progress['updated_at'] = now()->toDateTimeString();
            $progress['metrics'] = $this->getCurrentMetrics();
            
            Cache::put('scrape_progress', $progress, self::PROGRESS_CACHE_TTL_SECONDS);
            
        } catch (\Exception $e) {
            // Non-critical
        }
    }

    private function initializeMetrics(): void
    {
        $this->metrics = [
            'started_at' => microtime(true),
            'records_processed' => 0,
            'records_created' => 0,
            'records_updated' => 0,
            'records_skipped' => 0,
            'records_failed' => 0,
            'errors' => [],
            'bulk_documents' => 0,
            'standard_documents' => 0
        ];
    }

    private function validateOptions(): void
    {
        $fromOption = $this->option('from');
        $toOption = $this->option('to');
        
        if (($fromOption && !$toOption) || (!$fromOption && $toOption)) {
            throw new \InvalidArgumentException('Both --from and --to must be provided together');
        }
        
        if ($fromOption && $toOption) {
            $from = Carbon::parse($fromOption);
            $to = Carbon::parse($toOption);
            
            if ($from->gt($to)) {
                throw new \InvalidArgumentException('--from date must be before or equal to --to date');
            }
        }
        
        $limit = (int) $this->option('limit');
        if ($limit < 0) {
            throw new \InvalidArgumentException('--limit must be a positive integer or 0');
        }
        
        $batchSize = (int) $this->option('batch-size');
        if ($batchSize < 1 || $batchSize > 200) {
            throw new \InvalidArgumentException('--batch-size must be between 1 and 200');
        }
    }

    private function determineDateRange(): array
    {
        $fromOption = $this->option('from');
        $toOption = $this->option('to');
        
        $isManual = ($fromOption && $toOption);
        
        if (!$isManual) {
            $startLimit = Carbon::now()->subDays(7)->startOfDay();
            $endLimit = Carbon::now()->endOfDay();
        } else {
            $startLimit = Carbon::parse($fromOption)->startOfDay();
            $endLimit = Carbon::parse($toOption)->endOfDay();
        }
        
        return [$startLimit, $endLimit, $isManual];
    }

    private function connectDriver(bool $headless, int $timeout): void
    {
        $this->logToCache('🔌 Connecting to ChromeDriver...');
        
        $options = new ChromeOptions();
        
        // Use portable Chrome

        $portableChromePath = 'C:\\Budget\\Softwares\\bin_card\\control_card\\chrome-win64\\chrome.exe';
        
        if (file_exists($portableChromePath)) {
            $options->setBinary($portableChromePath);
            $this->logToCache("✅ Using portable Chrome");
        } else {
            $chromeBinary = env('CHROME_BINARY_PATH', null);
            if ($chromeBinary && file_exists($chromeBinary)) {
                $options->setBinary($chromeBinary);
            }
        }
        
        $arguments = [
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--window-size=1920,1080',
            '--disable-blink-features=AutomationControlled',
            '--disable-infobars',
            '--proxy-server=direct://',
            '--proxy-bypass-list=*',
            'user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ];
        
        if ($headless) {
            $arguments[] = '--headless=new';
            $this->logToCache('Running in headless mode');
        }
        
        $options->addArguments($arguments);
        $options->setExperimentalOption('excludeSwitches', ['enable-automation', 'enable-logging']);
        $options->setExperimentalOption('useAutomationExtension', false);
        
        $options->setExperimentalOption('prefs', [
            'download.default_directory' => sys_get_temp_dir(),
            'download.prompt_for_download' => false,
            'download.directory_upgrade' => true,
            'safebrowsing.enabled' => true
        ]);
        
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        $capabilities->setCapability('pageLoadStrategy', 'eager');
        $capabilities->setCapability('acceptInsecureCerts', true);
        
        $this->driver = RemoteWebDriver::create(
            self::CHROME_DRIVER_URL,
            $capabilities,
            $timeout * 1000,
            $timeout * 1000
        );
        
        $this->driver->executeScript("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})");
        
        $this->logToCache('✅ Chrome session established successfully', 'success');
    }

    /**
     * Wait for table to be fully loaded and stabilized
     */
    private function waitForTable(): void
    {
        $this->logToCache('⏳ Waiting for table to load...');
        
        // Wait for table element
        $this->driver->wait(20)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('table'))
        );
        
        $this->logToCache('⏳ Waiting for table data to populate...');
        
        // Wait for data to populate with retry
        $maxAttempts = 3;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $this->driver->wait(30)->until(function($driver) {
                    $cells = $driver->findElements(WebDriverBy::xpath("//table/tbody/tr[1]/td[6]"));
                    return count($cells) > 0 && !empty(trim($cells[0]->getText()));
                });
                break;
            } catch (\Exception $e) {
                if ($attempt === $maxAttempts) {
                    throw $e;
                }
                $this->logToCache("  Data population timeout, retrying... (attempt {$attempt}/{$maxAttempts})", 'warn');
                sleep(2);
            }
        }
        
        // Wait for all cells to be present
        $this->driver->wait(10)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('table tbody tr td'))
        );
        
        // Progressive stabilization - replace fixed sleep with intelligent waiting
        $this->logToCache('⏳ Waiting for table to stabilize...');
        
        $maxWait = self::TABLE_STABILIZE_SECONDS;
        $waited = 0;
        $stableCount = 0;
        $requiredStableChecks = 2;
        $lastRowCount = 0;
        
        while ($waited < $maxWait) {
            sleep(1);
            $waited++;
            
            try {
                // Check if table is stable by comparing row counts
                $currentRows = $this->driver->findElements(WebDriverBy::cssSelector('table tbody tr'));
                $currentRowCount = count($currentRows);
                
                // Also check if first row has content
                $hasContent = false;
                if ($currentRowCount > 0) {
                    $firstRowCells = $currentRows[0]->findElements(WebDriverBy::tagName('td'));
                    $hasContent = count($firstRowCells) > 0 && !empty(trim($firstRowCells[0]->getText()));
                }
                
                // Log progress every 2 seconds
                if ($waited % 2 === 0) {
                    $this->logToCache("  Stabilization check {$waited}/{$maxWait}: {$currentRowCount} rows, content: " . ($hasContent ? 'yes' : 'no'));
                }
                
                // Check if table is stable (row count hasn't changed and has content)
                if ($currentRowCount === $lastRowCount && $currentRowCount > 0 && $hasContent) {
                    $stableCount++;
                    if ($stableCount >= $requiredStableChecks) {
                        $this->logToCache("✅ Table stabilized after {$waited} seconds ({$currentRowCount} rows)", 'success');
                        return;
                    }
                } else {
                    // Reset stable count if table changed
                    $stableCount = 0;
                    $lastRowCount = $currentRowCount;
                }
                
            } catch (\Exception $e) {
                // If we can't check stability, just continue
                $this->logToCache("  Stability check failed: " . $e->getMessage(), 'warn');
            }
        }
        
        // If we reached max wait, log but continue anyway
        $this->logToCache("⚠️ Table stabilization timeout after {$maxWait} seconds, continuing anyway...", 'warn');
    }

    private function validateTableSchema(): void
    {
        try {
            $headers = $this->driver->findElements(WebDriverBy::cssSelector('table thead th'));
            
            if (count($headers) === 0) {
                $this->warn('Could not find table headers, skipping schema validation');
                return;
            }
            
            $this->logToCache('✅ Table schema validated', 'success');
            
        } catch (\Exception $e) {
            $this->warn("Schema validation warning: {$e->getMessage()}");
        }
    }

    private function captureTableSnapshot(Carbon $startLimit, Carbon $endLimit, bool $isManual): array
    {
        $this->logToCache('📸 Taking high-speed table snapshot...');

        // 1. Inject JavaScript to map the entire table instantly in Chrome's engine
        $jsScript = <<<'JS'
            let data = [];
            let rows = document.querySelectorAll('table tbody tr');
            
            rows.forEach(row => {
                let cells = row.querySelectorAll('td');
                if (cells.length < 6) return; // Skip if row is empty or incomplete
                
                let statusCell = cells[0];
                let mdaCell = cells[3];
                let refCell = cells[5];
                
                let ref = refCell.textContent.trim();
                if (!ref) return;

                // Grab the computed background color style natively
                let bgColor = window.getComputedStyle(statusCell).backgroundColor;
                let dateText = statusCell.textContent.trim();
                let mdaName = mdaCell.textContent.trim();

                data.push({
                    ref: ref,
                    mda_name: mdaName,
                    date_text: dateText,
                    bg_color: bgColor
                });
            });
            return data;
    JS;

        // Execute once. Zero internal loop delays.
        $rawSnapshot = $this->driver->executeScript($jsScript);
        $referenceChecklist = [];

        if (!is_array($rawSnapshot)) {
            return [];
        }

        // 2. Process dates and logic fast in native PHP memory
        foreach ($rawSnapshot as $item) {
            $status = $this->determineStatus($item['bg_color']);
            $shouldAdd = true;

            if ($isManual) {
                $releaseDate = Carbon::parse($this->parseDate($item['date_text']));
                if (!$releaseDate->between($startLimit, $endLimit)) {
                    $shouldAdd = false;
                }
            }

            if ($shouldAdd) {
                $referenceChecklist[] = [
                    'ref'            => $item['ref'],
                    'status'         => $status,
                    'mda_safety_net' => $item['mda_name']
                ];
            }
        }

        return $referenceChecklist;
    }

    private function determineStatus(string $bgColor): string
    {
        if (str_contains($bgColor, '255, 165, 0') || str_contains($bgColor, 'orange')) {
            return 'approved';
        }
        
        if (str_contains($bgColor, '139, 0, 0') || str_contains($bgColor, 'rgb(139, 0, 0)')) {
            return 'returned';
        }
        
        return 'circulating';
    }

    /**
     * Process record with infinite loop prevention
     */
    private function processRecordWithRetry(array $item, int $index, int $total): void
    {
        $targetRef = $item['ref'];
        
        // CRITICAL: Skip if already processed
        if (in_array($targetRef, $this->processedRefs)) {
            $this->logToCache("⏭️ Skipping already processed: {$targetRef}", 'warn');
            return;
        }
        
        $currentStatus = $item['status'];
        
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $this->processSingleRecord($item, $index, $total);
                $this->processedRefs[] = $targetRef; // Mark as processed
                $this->metrics['records_processed']++;
                $this->writeToCache(round(($index / $total) * 95), "Processed {$targetRef} ({$index}/{$total})");
                return;
                
            } catch (\Exception $e) {
                $this->logToCache("Attempt {$attempt}/" . self::MAX_RETRIES . " failed for {$targetRef}: " . $e->getMessage(), 'error');
                
                if ($attempt === self::MAX_RETRIES) {
                    $this->metrics['records_failed']++;
                    $this->metrics['errors'][] = [
                        'ref' => $targetRef,
                        'error' => $e->getMessage(),
                        'time' => now()->toDateTimeString()
                    ];
                    
                    Log::error('Record processing failed permanently', [
                        'ref' => $targetRef,
                        'error' => $e->getMessage()
                    ]);
                    
                    $this->recoverFromError();
                    return;
                }
                
                usleep(self::RETRY_DELAY_MS * 1000);
                $this->recoverFromError();
            }
        }
    }

    /**
     * Process single record - YOUR PROVEN LOGIC preserved
     */
    private function processSingleRecord(array $item, int $index, int $total): void
    {
        $targetRef = $item['ref'];
        $currentStatus = $item['status'];
        
        $this->logToCache("🔄 [{$index}/{$total}] Processing: {$targetRef} ({$currentStatus})");
        
        try {
            // Verify window is still valid
            try {
                $this->driver->getCurrentURL();
            } catch (\Exception $e) {
                $this->logToCache("⚠️ Window closed, reconnecting...", 'warn');
                $this->reconnectDriver();
            }
            
            // Locate row with better error handling
            $rowXPath = "//tr[td[6][normalize-space()='{$targetRef}']]";
            
            $targetRow = null;
            $maxAttempts = 3;
            
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $this->logToCache("  Looking for row: {$targetRef} (attempt {$attempt})");
                    $targetRow = $this->driver->wait(15)->until(
                        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::xpath($rowXPath))
                    );
                    break;
                } catch (\Exception $e) {
                    $this->logToCache("  Row not found: " . $e->getMessage(), 'warn');
                    if ($attempt == $maxAttempts) {
                        throw new \Exception("Could not locate row for reference '{$targetRef}' after {$maxAttempts} attempts");
                    }
                    $this->logToCache("  Refreshing table...", 'warn');
                    $this->driver->navigate()->refresh();
                    $this->waitForTable();
                    sleep(2);
                }
            }
            
            if (!$targetRow) {
                throw new \Exception("Could not locate row for reference: {$targetRef}");
            }
            
            $this->driver->executeScript("arguments[0].scrollIntoView({block: 'center'});", [$targetRow]);
            usleep(500000);
            
            // Find and click view button
            $viewBtn = $targetRow->findElement(WebDriverBy::xpath(".//button[contains(., 'View')]"));
            $this->driver->executeScript("arguments[0].click();", [$viewBtn]);
            
            // Wait for modal/detail page to load
            sleep(2);
            
            // YOUR PROVEN MDA extraction logic with better error handling
            $portalMdaName = null;
            $snappedMdaName = $item['mda_safety_net'] ?? 'Unknown MDA';
            
            try {
                $this->logToCache("🔍 Checking for Release Details table...");
                
                $mdaHeaderXpath = "//th[contains(text(), 'MDA')]";
                $this->driver->wait(5)->until(
                    WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::xpath($mdaHeaderXpath))
                );
                
                $detailsTableXpath = "//div[contains(., 'Release Details')]/following-sibling::div[contains(@class, 'table-responsive')]//table";
                $mdaNameXpath = $detailsTableXpath . "//tbody/tr[1]/td[2]";
                
                $portalMdaName = trim($this->driver->findElement(WebDriverBy::xpath($mdaNameXpath))->getText());
                $this->logToCache("📍 Found MDA via Table: " . $portalMdaName);
                
                // Try to click preview document button
                try {
                    $previewBtnXpath = "//button[contains(@class, 'btn-link') and contains(text(), 'Preview Document')]";
                    $previewBtn = $this->driver->findElement(WebDriverBy::xpath($previewBtnXpath));
                    
                    $this->driver->executeScript("window.scrollTo(0, 0);"); 
                    $this->driver->executeScript("arguments[0].click();", [$previewBtn]);
                    
                    $this->logToCache("Preview Button clicked for: " . $portalMdaName, 'success');
                    sleep(self::PREVIEW_DOCUMENT_WAIT_SECONDS);
                } catch (\Exception $e) {
                    $this->logToCache("⚠️ Preview Document button missing.", 'warn');
                }
                
            } catch (\Exception $e) {
                $portalMdaName = $snappedMdaName;
                $this->logToCache("⚡ Using Safety-Net MDA: " . $portalMdaName);
            }
            
            $portalMdaName = $portalMdaName ?: $snappedMdaName;
            
            $this->logToCache("📄 Parsing document structure for: " . $portalMdaName);
            
            // Extract data using YOUR PROVEN methods
            $extractedRecords = $this->extractDataFromDocument(
                $this->driver,
                $portalMdaName,
                $currentStatus,
                $targetRef
            );
            
            $validatedRecords = $this->validateExtractedRecords($extractedRecords);
            
            if (count($validatedRecords) > 0) {
                $this->saveRecordsWithRetry($validatedRecords);
            } else {
                $this->logToCache("No valid records extracted for {$targetRef}", 'warn');
            }
            
            // Navigate back safely
            try {
                $this->driver->navigate()->back();
            } catch (\Exception $e) {
                $this->logToCache("Back navigation failed, reloading portal...", 'warn');
                $this->driver->get(self::PORTAL_URL);
            }
            
            // YOUR PROVEN wait after navigation
            try {
                $this->driver->wait(60)->until(
                    WebDriverExpectedCondition::visibilityOfElementLocated(
                        WebDriverBy::tagName('table')
                    )
                );
            } catch (\Exception $e) {
                $this->logToCache("Table not visible after navigation, reloading...", 'warn');
                $this->driver->get(self::PORTAL_URL);
                $this->waitForTable();
            }
            
            usleep(500000);
            
        } catch (\Exception $e) {
            $this->logToCache("Error processing record {$targetRef}: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    /**
     * Save records with SQLite lock prevention
     */
    private function saveRecordsWithRetry(array $extractedRecords, int $maxRetries = 5): void
    {
        foreach ($extractedRecords as $record) {
            $attempt = 0;
            $saved = false;
            
            while ($attempt < $maxRetries && !$saved) {
                try {
                    DB::transaction(function() use ($record) {
                        $ref = trim($record['reference_no']);
                        $mda = trim($record['mda_code']);
                        $sub = trim($record['subhead_code']);
                        $amt = (float) $record['amount'];
                        $date = $record['release_date'];
                        $narr = trim($record['narration']);
                        $stat = $record['status'];
                        
                        // Validate required fields before database operations
                        if (empty($ref) || empty($mda) || empty($sub) || $amt <= 0) {
                            $this->logToCache("  ⚠️ Invalid record data for {$ref}", 'warn');
                            $this->metrics['records_skipped']++;
                            return;
                        }
                        
                        // Check permanent ledger
                        $inMainLedger = Release::where('reference_no', $ref)
                            ->where('mda_code', $mda)
                            ->where('subhead_code', $sub)
                            ->where('amount', $amt)
                            ->exists();
                        
                        if ($inMainLedger) {
                            $this->logToCache("  🚫 Skipped: Already in main ledger - {$ref}");
                            $this->metrics['records_skipped']++;
                            return;
                        }
                        
                        // Check staging with lock to prevent race conditions
                        $existingStaged = ScrapedRelease::where('reference_no', $ref)
                            ->where('mda_code', $mda)
                            ->where('subhead_code', $sub)
                            ->where('amount', $amt)
                            ->lockForUpdate()
                            ->first();
                        
                        if ($existingStaged) {
                            $isExactMatch = (
                                $existingStaged->release_date == $date &&
                                $existingStaged->status === $stat &&
                                $existingStaged->narration === $narr
                            );
                            
                            if ($isExactMatch) {
                                $this->logToCache("  ⏩ Skipped: Perfect match - {$ref}");
                                $this->metrics['records_skipped']++;
                                return;
                            }
                            
                            // Update existing record
                            $existingStaged->update([
                                'release_date' => $date,
                                'status' => $stat,
                                'narration' => $narr,
                                'mda_name' => $record['mda_name'] ?? $existingStaged->mda_name,
                                'last_seen_at' => now()
                            ]);
                            
                            $this->logToCache("  🔄 Updated: {$ref} → {$stat}");
                            $this->metrics['records_updated']++;
                            return;
                        }
                        
                        // Create new record with status history
                        $statusHistory = json_encode([[
                            'status' => $stat,
                            'amount' => $amt,
                            'changed_at' => now()->toDateTimeString()
                        ]]);
                        
                        ScrapedRelease::create([
                            'reference_no' => $ref,
                            'subhead_code' => $sub,
                            'mda_code' => $mda,
                            'mda_name' => $record['mda_name'] ?? 'N/A',
                            'release_date' => $date,
                            'amount' => $amt,
                            'narration' => $narr,
                            'status' => $stat,
                            'last_seen_at' => now(),
                            'status_history' => $statusHistory
                        ]);
                        
                        $this->logToCache("🆕 Created: {$ref} ({$stat})", 'success');
                        $this->metrics['records_created']++;
                        
                    }, 3); // transaction retry 3 times
                    
                    $saved = true;
                    
                } catch (\Illuminate\Database\QueryException $e) {
                    $attempt++;
                    
                    // Check if it's a duplicate key error
                    if (str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                        $this->logToCache("  ⚠️ Duplicate record detected, skipping...", 'warn');
                        $this->metrics['records_skipped']++;
                        $saved = true; // Exit loop, treat as saved (skipped)
                        break;
                    }
                    
                    // Check if it's a database lock
                    if (str_contains($e->getMessage(), 'database is locked')) {
                        if ($attempt >= $maxRetries) {
                            $this->logToCache("  ❌ Failed to save record after {$maxRetries} attempts (DB lock): " . $e->getMessage(), 'error');
                            $this->metrics['records_failed']++;
                        } else {
                            $this->logToCache("  ⚠️ DB lock, retrying... (attempt {$attempt}/{$maxRetries})", 'warn');
                            usleep(500000 * $attempt); // Progressive delay: 0.5s, 1s, 1.5s, etc.
                        }
                    } else {
                        // Other database error
                        if ($attempt >= $maxRetries) {
                            $this->logToCache("  ❌ Failed to save record after {$maxRetries} attempts: " . $e->getMessage(), 'error');
                            $this->metrics['records_failed']++;
                        } else {
                            $this->logToCache("  ⚠️ DB error, retrying... (attempt {$attempt}/{$maxRetries}): " . $e->getMessage(), 'warn');
                            usleep(500000);
                        }
                    }
                    
                } catch (\Exception $e) {
                    $attempt++;
                    
                    if ($attempt >= $maxRetries) {
                        $this->logToCache("  ❌ Failed to save record after {$maxRetries} attempts: " . $e->getMessage(), 'error');
                        $this->metrics['records_failed']++;
                        
                        // Log detailed error for debugging
                        Log::error('Record save failed', [
                            'record' => $record,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    } else {
                        $this->logToCache("  ⚠️ Error, retrying... (attempt {$attempt}/{$maxRetries}): " . $e->getMessage(), 'warn');
                        usleep(500000);
                    }
                }
            }
        }
    }

    /**
     * YOUR PROVEN extraction methods - preserved exactly
     */
    private function extractDataFromDocument($driver, string $portalMdaName, string $status, string $fallbackRef): array
    {
        $tableRows = $driver->findElements(WebDriverBy::cssSelector('table tr'));
        
        // Get the document content directly from the visible document div
        $documentText = $this->getDocumentText($driver);
        
        $isBulk = (count($tableRows) > 2);
        
        if ($isBulk) {
            $this->logToCache("📊 Format Detected: BULK (multi-row salary document)");
            $this->metrics['bulk_documents']++;
            return $this->scrapeBulkSalaryFormat($driver, $documentText, $status, $fallbackRef);
        }
        
        $this->logToCache("📄 Format Detected: STANDARD (single release document)");
        $this->metrics['standard_documents']++;
        return [$this->parseStandardLetter($documentText, $portalMdaName, $status, $fallbackRef)];
    }

    /**
     * Get the document text content (not the whole page)
     */
    private function getDocumentText($driver): string
    {
        try {
            // Try to find the document container (the printable area)
            $documentDiv = $driver->findElement(WebDriverBy::xpath("//div[contains(@style, '210mm')]"));
            if ($documentDiv) {
                $text = $documentDiv->getText();
                if (!empty($text)) {
                    return $text;
                }
            }
        } catch (\Exception $e) {
            // Not found, fallback
            $this->logToCache("  Document container not found, using body text", 'warn');
        }
        
        // Fallback: get body text
        return $driver->findElement(WebDriverBy::tagName('body'))->getText();
    }

    /**
     * Extract only the document/modal content from the page
     */
    // private function extractDocumentContent($driver): string
    // {
    //     // Try multiple strategies to get the document content
        
    //     // Strategy 1: Look for the document preview modal
    //     try {
    //         $modalContent = $driver->findElement(WebDriverBy::cssSelector('.modal-content, .document-preview, .release-document'));
    //         if ($modalContent) {
    //             return $modalContent->getAttribute('innerHTML');
    //         }
    //     } catch (\Exception $e) {
    //         // Not found, continue
    //     }
        
    //     // Strategy 2: Look for the document container div
    //     try {
    //         $documentDiv = $driver->findElement(WebDriverBy::xpath("//div[contains(@class, 'document-container') or contains(@style, '210mm')]"));
    //         if ($documentDiv) {
    //             return $documentDiv->getAttribute('innerHTML');
    //         }
    //     } catch (\Exception $e) {
    //         // Not found, continue
    //     }
        
    //     // Strategy 3: Get the body but try to exclude chat widget
    //     $fullHtml = $driver->getPageSource();
        
    //     // Remove chat widget content if present
    //     $fullHtml = preg_replace('/<div class="_1yCVn _--3fm">.*?<\/div><div class="_2qp0Z">.*?<\/div>/s', '', $fullHtml);
    //     $fullHtml = preg_replace('/<div class="Toastify"><\/div>.*$/s', '', $fullHtml);
        
    //     return $fullHtml;
    // }

    private function scrapeBulkSalaryFormat($driver, $fullText, $status, $fallbackRef): array
    {
        $stagedData = [];
        $currentMdaCode = null; 
        $currentMdaName = 'N/A';
        
        $globalRef = $fallbackRef; 
        if (preg_match('/Our Ref:\s*([A-Z0-9\/\.\-]+)/i', strip_tags($fullText), $m)) {
            $globalRef = trim($m[1]);
        }
        
        $dateMatch = $this->regexMatch('/\d{1,2}(?:st|nd|rd|th)?\s+\w+,\s+\d{4}/i', $fullText);
        $globalDate = $this->parseDate($dateMatch);
        
        // ============================================================
        // FIXED NARRATION EXTRACTION FOR BULK RELEASES
        // Since $fullText is plain text (getText), we look for the sentence
        // ============================================================
        $globalNarration = "Special Release of Funds";
        
        // Anchor pattern - starts with "I am directed" or similar
        $anchor = '(?:I\s+am\s+directed|I\s+wish\s+.*?\s+am\s+directed|enable|effect|facilitate|cater|settle|offset|being|for\s+the\s+implementation|payment\s+of|reimburse|payable|for\s+payment)';
        
        // Since getText() strips HTML, we look for the anchor text until:
        // - End of sentence (period followed by space and capital letter)
        // - Or until we see "CC:" or "The Vote of Charge" (if present)
        // - Or until we see a line that looks like a table (multiple spaces/numbers)
        
        $terminationPattern = '(?:\.\s+(?=[A-Z])|CC:|The\s+Vote\s+of\s+Charge|DIRECTOR\s+OF\s+BUDGET|$\n\s*\n)';
        $pattern = '/(' . $anchor . '.*?)(?=' . $terminationPattern . ')/is';
        
        if (preg_match($pattern, $fullText, $matches)) {
            $globalNarration = trim($matches[1]);
            $globalNarration = preg_replace('/\s+/', ' ', $globalNarration);
            $globalNarration = rtrim($globalNarration, ':. ');
        }
        
        // Ensure proper ending punctuation
        if (!empty($globalNarration) && $globalNarration !== "Special Release of Funds") {
            if (str_contains(strtolower($globalNarration), 'as follows')) {
                $globalNarration = rtrim($globalNarration, ':. ') . ':';
            } else {
                $globalNarration = rtrim($globalNarration, ':. ') . '.';
            }
        } else {
            $globalNarration = "Special Release of Funds.";
        }
        // ============================================================
        
        sleep(1); 
        $tableRows = $driver->findElements(WebDriverBy::cssSelector('table tr'));
        $rowCount = 0;
        
        foreach ($tableRows as $row) {
            $cols = $row->findElements(WebDriverBy::tagName('td'));
            if (count($cols) < 3) continue; 
            
            $firstColRaw = trim($cols[0]->getText());
            $cleanCode = preg_replace('/[^0-9]/', '', $firstColRaw);
            if (empty($cleanCode)) continue; 
            
            if (strlen($cleanCode) === 12) {
                $currentMdaCode = $cleanCode; 
                if (isset($cols[1])) {
                    $currentMdaName = trim($cols[1]->getText());
                }
                continue; 
            }
            
            $codeLength = strlen($cleanCode);
            if ($currentMdaCode && ($codeLength >= 8 && $codeLength <= 11)) {
                $subheadCode = $cleanCode;
                
                if (str_starts_with($subheadCode, '0') && ($codeLength === 9 || $codeLength === 11)) {
                    $subheadCode = substr($subheadCode, 1);
                }
                
                $amount = 0;
                $maxRetries = 3;
                $retryCount = 0;
                
                while ($retryCount < $maxRetries) {
                    $amountRaw = $driver->executeScript("return arguments[0].textContent;", [$cols[2]]);
                    $amountClean = preg_replace('/[^0-9.]/', '', $amountRaw);
                    $amount = (float) $amountClean;
                    if ($amount > 0) break; 
                    sleep(1); 
                    $retryCount++;
                } 
                
                if ($amount > 0) {
                    $stagedData[] = [
                        'reference_no' => $globalRef,
                        'release_date' => $globalDate,
                        'narration'    => $globalNarration,
                        'mda_code'     => $currentMdaCode,
                        'mda_name'     => $currentMdaName,
                        'subhead_code' => $subheadCode,
                        'amount'       => $amount,
                        'status'       => $status
                    ];
                    $rowCount++;
                }
            }
        }
        
        $this->logToCache("  Extracted {$rowCount} line items from bulk document");
        return $stagedData;
    }    
    
    private function parseStandardLetter(string $fullText, string $portalMdaName, string $status, string $fallbackRef): array
    {
        // Extract reference
        $extractedRef = $this->regexMatch('/Our Ref:\s*([^\n\r]+)/i', $fullText);
        
        // Extract amount - look for pattern in the document text
        $amount = 0;
        // Pattern for (N2,670,000.00)
        if (preg_match('/\(N?([\d,]+(?:\.\d{2})?)\)/', $fullText, $amountMatch)) {
            $amount = $this->cleanAmount($amountMatch[1]);
        }
        // Pattern for N2,670,000.00
        elseif (preg_match('/N([\d,]+(?:\.\d{2})?)/', $fullText, $amountMatch)) {
            $amount = $this->cleanAmount($amountMatch[1]);
        }
        
        // Extract date
        $dateMatch = $this->regexMatch('/\d{1,2}(?:st|nd|rd|th)?\s+\w+,\s+\d{4}/i', $fullText);
        
        $data = [
            'reference_no' => (!empty($extractedRef) && $extractedRef !== "N/A") ? $extractedRef : $fallbackRef,
            'release_date' => $this->parseDate($dateMatch),
            'amount'       => $amount,
            'narration'    => 'Standard Release',
            'mda_code'     => 'N/A',
            'mda_name'     => $portalMdaName, 
            'subhead_code' => 'N/A',
            'status'       => $status
        ];
        
        // Extract narration - capture from "I am directed" until "The Vote of Charge"
        $anchor = '(?:I\s+am\s+directed|I\s+wish\s+.*?\s+am\s+directed)';
        if (preg_match('/(' . $anchor . '.*?)(?=The Vote of Charge|CC:|$)/is', $fullText, $matches)) {
            $narration = trim($matches[1]);
            $narration = preg_replace('/\s+/', ' ', $narration);
            $narration = rtrim($narration, ':. ');
            $data['narration'] = $narration . '.';
        }
        
        // Extract MDA and subhead codes
        if (preg_match('/(\d{12})\/(\d{8,11})/', $fullText, $m)) {
            $data['mda_code'] = $m[1];
            $rawSubhead = $m[2];
            $length = strlen($rawSubhead);
            
            if (str_starts_with($rawSubhead, '0') && ($length === 9 || $length === 11)) {
                $data['subhead_code'] = substr($rawSubhead, 1);
            } else {
                $data['subhead_code'] = $rawSubhead;
            }
        }
        
        $this->logToCache("  Amount extracted: {$data['amount']} for {$data['reference_no']}");
        
        return $data;
    }
    
    private function validateExtractedRecords(array $records): array
    {
        $validated = [];
        
        foreach ($records as $record) {
            if (empty($record['reference_no'])) {
                $this->logToCache("Skipping record: Missing reference number", 'warn');
                continue;
            }
            
            if (empty($record['mda_code'])) {
                $this->logToCache("Skipping record {$record['reference_no']}: Missing MDA code", 'warn');
                continue;
            }
            
            if (empty($record['subhead_code'])) {
                $this->logToCache("Skipping record {$record['reference_no']}: Missing subhead code", 'warn');
                continue;
            }
            
            if ($record['amount'] <= 0) {
                $this->logToCache("Skipping record {$record['reference_no']}: Invalid amount {$record['amount']}", 'warn');
                continue;
            }
            
            if (!in_array($record['status'], ['circulating', 'approved', 'returned'])) {
                $record['status'] = 'circulating';
            }
            
            $validated[] = $record;
        }
        
        return $validated;
    }
    
    private function recoverFromError(): void
    {
        try {
            // Check if window is still valid
            try {
                $this->driver->getCurrentURL();
            } catch (\Exception $e) {
                $this->logToCache("⚠️ Window lost during recovery, reconnecting driver...", 'warn');
                $this->reconnectDriver();
                return;
            }
            
            // Try to recover by going back to portal
            $this->logToCache("🔄 Attempting recovery by reloading portal...", 'warn');
            $this->driver->get(self::PORTAL_URL);
            $this->waitForTable();
            $this->logToCache("✅ Recovery successful", 'success');
            
        } catch (\Exception $e) {
            $this->logToCache("❌ Recovery failed, performing full reconnect...", 'error');
            $this->reconnectDriver();
        }
    }
    private function cleanupMemory(): void
    {
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
    
    private function getCurrentMetrics(): array
    {
        return [
            'processed' => $this->metrics['records_processed'],
            'created' => $this->metrics['records_created'],
            'updated' => $this->metrics['records_updated'],
            'skipped' => $this->metrics['records_skipped'],
            'failed' => $this->metrics['records_failed']
        ];
    }
    
    private function completeScraping(): void
    {
        $this->writeToCache(100, 'Scraping completed successfully');
        $this->logToCache('Scraping completed successfully', 'success');
        $this->logToCache('Staging environment has been safely refreshed.', 'success');
        $this->flushLogBuffer(); // Ensure all logs are written
        
        $this->info('');
        $this->info('✅ ' . str_repeat('=', 60));
        $this->info('SCRAPING COMPLETED SUCCESSFULLY');
        $this->info('✅ ' . str_repeat('=', 60));
    }
    
    private function logFinalMetrics(): void
    {
        $duration = round(microtime(true) - $this->metrics['started_at'], 2);
        
        $this->logToCache("[SYSTEM] " . str_repeat('=', 50));
        $this->logToCache("[SUCCESS] FINAL METRICS:");
        $this->logToCache("[SUCCESS] Duration: {$duration} seconds");
        $this->logToCache("[SUCCESS] Processed: {$this->metrics['records_processed']}");
        $this->logToCache("[SUCCESS] Created: {$this->metrics['records_created']}");
        $this->logToCache("[SUCCESS] Updated: {$this->metrics['records_updated']}");
        $this->logToCache("[SUCCESS] Skipped: {$this->metrics['records_skipped']}");
        $this->logToCache("[SUCCESS] Failed: {$this->metrics['records_failed']}");
        $this->logToCache("[SUCCESS] Bulk Documents: {$this->metrics['bulk_documents']}");
        $this->logToCache("[SUCCESS] Standard Documents: {$this->metrics['standard_documents']}");
        $this->logToCache("[SYSTEM] " . str_repeat('=', 50));
        
        $finalMetrics = [
            'duration_seconds' => $duration,
            'records_processed' => $this->metrics['records_processed'],
            'records_created' => $this->metrics['records_created'],
            'records_updated' => $this->metrics['records_updated'],
            'records_skipped' => $this->metrics['records_skipped'],
            'records_failed' => $this->metrics['records_failed'],
            'bulk_documents' => $this->metrics['bulk_documents'],
            'standard_documents' => $this->metrics['standard_documents'],
            'error_count' => count($this->metrics['errors']),
            'completed_at' => now()->toDateTimeString()
        ];
        
        Cache::put('scrape_metrics_last_run', $finalMetrics, 86400);
        
        $this->info('');
        $this->info('📊 FINAL METRICS:');
        $this->info("   Duration: {$duration} seconds");
        $this->info("   Processed: {$this->metrics['records_processed']}");
        $this->info("   Created: {$this->metrics['records_created']}");
        $this->info("   Updated: {$this->metrics['records_updated']}");
        $this->info("   Skipped: {$this->metrics['records_skipped']}");
        $this->info("   Failed: {$this->metrics['records_failed']}");
    }
    
    private function handleCriticalFailure(\Exception $e): void
    {
        $errorMessage = $e->getMessage();
        if (empty($errorMessage)) {
            $errorMessage = get_class($e) . " - Check WebDriver connection and Chrome version";
        }
        
        $this->logToCache('💀 CRITICAL FAILURE: ' . $errorMessage, 'error');
        $this->logToCache('File: ' . $e->getFile() . ':' . $e->getLine(), 'error');
        $this->flushLogBuffer();
        
        $this->error('');
        $this->error('💀 CRITICAL FAILURE');
        $this->error('Message: ' . $errorMessage);
        $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
        
        Log::error('Critical scraping failure', [
            'message' => $errorMessage,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    } 

    private function disconnectDriver(): void
    {
        try {
            if ($this->driver) {
                $this->driver->quit();
                $this->logToCache('🔌 Driver disconnected');
                $this->flushLogBuffer();
            }
        } catch (\Exception $e) {
            Log::warning('Error disconnecting driver: ' . $e->getMessage());
        }
    }
    
    private function regexMatch(string $pattern, string $text): string
    {
        return preg_match($pattern, $text, $matches) ? trim($matches[1] ?? $matches[0]) : '';
    }
    
    private function cleanAmount(string $value): float
    {
        return (float) str_replace(['₦', ',', ' '], '', $value);
    }
    
    private function parseDate(?string $dateStr): string
    {
        if (!$dateStr) {
            return now()->format('Y-m-d');
        }
        
        $clean = preg_replace('/(\d+)(st|nd|rd|th)/i', '$1', $dateStr);
        $timestamp = strtotime($clean);
        
        return $timestamp ? date('Y-m-d', $timestamp) : now()->format('Y-m-d');
    }

    private function reconnectDriver(): void
    {
        try {
            if ($this->driver) {
                try {
                    $this->driver->quit();
                } catch (\Exception $e) {
                    // Ignore quit errors
                    $this->logToCache("⚠️ Error quitting driver: " . $e->getMessage(), 'warn');
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }
        
        $isHeadless = filter_var($this->option('headless'), FILTER_VALIDATE_BOOLEAN);
        $timeout = (int) $this->option('timeout');
        
        $this->connectDriver($isHeadless, $timeout);
        $this->driver->get(self::PORTAL_URL);
        $this->waitForTable();
        
        $this->logToCache("✅ Driver reconnected successfully", 'success');
    }
    /**
     * Extract amount from HTML content
     * Handles patterns like: (N2,670,000.00) or N2,670,000.00 or 2,670,000.00
     */
    private function extractAmountFromHtml(string $html): float
    {
        // Pattern 1: (N2,670,000.00) or (2,670,000.00)
        $pattern1 = '/\(N?([\d,]+(?:\.\d{2})?)\)/i';
        if (preg_match($pattern1, $html, $matches)) {
            return $this->cleanAmount($matches[1]);
        }
        
        // Pattern 2: N2,670,000.00 (without parentheses)
        $pattern2 = '/N([\d,]+(?:\.\d{2})?)/i';
        if (preg_match($pattern2, $html, $matches)) {
            return $this->cleanAmount($matches[1]);
        }
        
        // Pattern 3: Just the number with commas and decimals
        $pattern3 = '/([\d,]+(?:\.\d{2})?)/';
        if (preg_match($pattern3, $html, $matches)) {
            return $this->cleanAmount($matches[1]);
        }
        
        return 0;
    }
    /**
     * Safe wait with timeout and error handling
     */
    // private function safeWait(callable $condition, int $timeoutSeconds = 30, string $errorMessage = 'Condition not met'): bool
    // {
    //     $startTime = microtime(true);
    //     $endTime = $startTime + $timeoutSeconds;
        
    //     while (microtime(true) < $endTime) {
    //         try {
    //             if ($condition()) {
    //                 return true;
    //             }
    //         } catch (\Exception $e) {
    //             // Ignore errors during wait, just continue
    //         }
    //         usleep(250000); // Sleep 0.25 seconds
    //     }
        
    //     $this->logToCache("⚠️ Wait timeout: {$errorMessage}", 'warn');
    //     return false;
    // }
}