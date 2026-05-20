<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScrapedRelease;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ScrapeExpenditure extends Command
{
    /**
     * The name and signature of the console command.
     * The {ref?} is an optional argument. Options start with --
     *
     * @var string
     */
    protected $signature = 'budget:scrape {--limit=8} {--from=2026-01-01} {--to=2026-01-31}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrapes expenditure data from the Katsina State transparency portal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        // Get raw options
        $fromOption = $this->option('from');
        $toOption   = $this->option('to');

        /**
         * FLAG: Is this a manual historical scrape?
         * If the user provides custom dates, $isManual becomes true.
         * If they leave the defaults, it's false, and we trust the portal's weekly view.
         */
        $isManual = ($fromOption !== '2026-01-01' || $toOption !== '2026-01-31');

        if (!$isManual) {
            // DEFAULT: Trust the portal's built-in 1-week filter
            $startLimit = Carbon::now()->subDays(7)->startOfDay();
            $endLimit   = Carbon::now()->endOfDay();
            $this->info("📅 Mode: Automatic (Trusting portal's 1-week default)");
        } else {
            // MANUAL: Use the specific dates provided in the terminal
            $startLimit = Carbon::parse($fromOption)->startOfDay();
            $endLimit   = Carbon::parse($toOption)->endOfDay();
            $this->info("📅 Mode: Manual Scrape (Filtering for: " . $startLimit->toDateString() . " to " . $endLimit->toDateString() . ")");
        }

        $this->info("🚀 Initializing Scraper Engine...");

        $options = new ChromeOptions();
        $options->addArguments([
            '--headless=new', // This runs Chrome in the background
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--window-size=960,1080',
            // 1. HIDERS
            '--disable-blink-features=AutomationControlled',
            '--disable-infobars',
            // 2. TIMEOUT FIX: Don't wait for useless tracking scripts to load
            '--proxy-server=' . 'direct://',
            '--proxy-bypass-list=*',
            // 3. FINGERPRINT MASKING
            'user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        ]);

        // 4. REMOVE CHROME-DRIVER SIGNATURES (CRITICAL)
        $options->setExperimentalOption('excludeSwitches', ['enable-automation', 'enable-logging']);
        $options->setExperimentalOption('useAutomationExtension', false);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        // 5. PAGE LOAD STRATEGY: This tells Chrome "Don't wait for everything to finish"
        // This prevents the Curl Timeout if a single image or script hangs.
        $capabilities->setCapability('pageLoadStrategy', 'eager');

        $driver = null;

        try {
            $driver = RemoteWebDriver::create('http://127.0.0.1:9515', $capabilities, 300000, 300000);
            $driver->executeScript("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})");
            
            $this->info("Step 1: Navigating to Portal...");
            $driver->get('https://kteb.katsinastate.gov.ng/release-collection');

            // 1. Wait for the table shell to exist
            $driver->wait(20)->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('table'))
            );

            $this->info("⏳ Waiting for table data to populate...");

            // 2. CRITICAL: Wait until at least one <td> in the 6th column has text (the Reference No)
            $driver->wait(30)->until(function($driver) {
                $cells = $driver->findElements(WebDriverBy::xpath("//table/tbody/tr[1]/td[6]"));
                return count($cells) > 0 && !empty(trim($cells[0]->getText()));
            });

            $this->info("⏳ Waiting for table to stabilize...");

            // Wait up to 10 seconds for the table to actually have data rows
            $driver->wait(10)->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('table tbody tr td'))
            );

            // Extra safety: Wait an additional 2 seconds for any background JS to finish coloring the rows
            
            //Uncomment the two lines below for manual filter reset
            // $this->info("🛑 PAUSED: Set your filters in Chrome.");
            // $this->confirm("Press Enter when the table is ready", true);
            
            sleep(2);

            $this->info("📸 Taking table snapshot...");

            // The :has(td) part ensures we skip rows that don't have data cells (like headers)
            $rows = $driver->findElements(WebDriverBy::cssSelector('table tbody tr:has(td)'));
            
            $referenceChecklist = []; // Initialize as empty array

            foreach ($rows as $row) {
                try {
                    // 1. Grab the Reference No from Column 6
                    $refCell = $row->findElement(WebDriverBy::xpath("./td[6]"));
                    $ref = trim($refCell->getText());
                    if (empty($ref)) continue;

                    // NEW: Grab the MDA Name from Column 4 (The Safety Net)
                    $mdaCell = $row->findElement(WebDriverBy::xpath("./td[4]"));
                    $snappedMdaName = trim($mdaCell->getText());

                    // 2. DETECT STATUS FROM COLOR (Column 1 usually holds the color/date)
                    $statusCell = $row->findElement(WebDriverBy::xpath("./td[1]"));
                    $bgColor = $statusCell->getCssValue('background-color');
                    
                    $status = 'circulating'; // Default state
                    
                    // Orange Detection (Approved)
                    if (str_contains($bgColor, '255, 165, 0') || str_contains($bgColor, 'orange')) {
                        $status = 'approved';
                    } 
                    // Dark Red Detection (Returned/Rejected)
                    elseif (str_contains($bgColor, '139, 0, 0') || str_contains($bgColor, 'rgb(139, 0, 0)')) {
                        $status = 'returned';
                    }

                    // 3. Date Filtering (Manual vs Automatic)
                    $shouldAdd = false;
                    if ($isManual) {
                        $dateText = $statusCell->getText();
                        $releaseDate = Carbon::parse($this->parseDate($dateText));

                        if ($releaseDate->between($startLimit, $endLimit)) {
                            $shouldAdd = true;
                        }
                    } else {
                        // Automatic Mode: Just take everything
                        $shouldAdd = true;
                    }

                    if ($shouldAdd) {
                        $referenceChecklist[] = [
                            'ref'            => $ref, 
                            'status'         => $status,
                            'mda_safety_net' => $snappedMdaName // Attached here for the detail page to use
                        ];
                    }

                } catch (\Exception $e) { 
                    $this->error("Error snapshotting row: " . $e->getMessage());
                    continue; 
                }
            }

            if ($limit > 0) $referenceChecklist = array_slice($referenceChecklist, 0, $limit);

            $total = count($referenceChecklist);
            $this->info("Snapshot complete. Found $total records.");

            foreach ($referenceChecklist as $index => $item) {
                // 1. Unpack the array from our table snapshot
                $targetRef = $item['ref'];
                $currentStatus = $item['status'];

                // 2. Save progress to the Cache
                $percent = round((($index + 1) / $total) * 100);
                cache()->put('scrape_progress', [
                    'percent' => $percent,
                    'status' => "Processing $targetRef (" . ($index + 1) . "/$total)"
                ], 600); 

                $this->info("🔄 [" . ($index + 1) . "/$total] Processing: $targetRef ($currentStatus)");

                try {
                    // Find the row in the table
                    $rowXPath = "//tr[td[6][normalize-space()='$targetRef']]";
                    $targetRow = $driver->wait(15)->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::xpath($rowXPath)));

                    $driver->executeScript("arguments[0].scrollIntoView({block: 'center'});", [$targetRow]);
                    usleep(500000);

                    // Click View
                    $viewBtn = $targetRow->findElement(WebDriverBy::xpath(".//button[contains(., 'View')]"));
                    $driver->executeScript("arguments[0].click();", [$viewBtn]);

                    // =================================================================
                    // UPDATED LOGIC: HANDLE BOTH INTERMEDIATE TABLE AND DIRECT VIEW
                    // =================================================================
                    // Assume $item is the current record from your $referenceChecklist loop
                    $portalMdaName = null;
                    $snappedMdaName = $item['mda_safety_net'] ?? 'Unknown MDA';

                    try {
                        $this->info("🔍 Checking for Release Details table...");
                        
                        // 1. Wait for the Details Header (Short wait to detect if we branched)
                        $mdaHeaderXpath = "//th[contains(text(), 'MDA')]";
                        $driver->wait(10)->until(
                            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::xpath($mdaHeaderXpath))
                        );

                        // 2. Extract MDA Name from the "Release Details" card
                        $detailsTableXpath = "//div[contains(., 'Release Details')]/following-sibling::div[contains(@class, 'table-responsive')]//table";
                        $mdaNameXpath = $detailsTableXpath . "//tbody/tr[1]/td[2]";
                        
                        $portalMdaName = trim($driver->findElement(WebDriverBy::xpath($mdaNameXpath))->getText());
                        $this->info("📍 Found MDA via Table: " . $portalMdaName);

                        // 3. Click Preview Button (Only if the intermediate table exists)
                        try {
                            $previewBtnXpath = "//button[contains(@class, 'btn-link') and contains(text(), 'Preview Document')]";
                            $previewBtn = $driver->findElement(WebDriverBy::xpath($previewBtnXpath));
                            
                            $driver->executeScript("window.scrollTo(0, 0);"); 
                            $driver->executeScript("arguments[0].click();", [$previewBtn]);
                            
                            $this->info("✅ Preview Button clicked for: " . $portalMdaName);
                            usleep(5000000); // Wait for document/modal to load
                        } catch (\Exception $e) {
                            $this->warn("⚠️ Table found, but 'Preview Document' button missing.");
                        }

                    } catch (\Exception $e) {
                        // FALLBACK: If table isn't found, use the name we snapped from the main list
                        $portalMdaName = $snappedMdaName;
                        $this->info("⚡ No table found. Using Snapped Safety-Net MDA: " . $portalMdaName);
                    }

                    // Final check: if for some reason both failed, ensure we aren't passing a null name
                    $portalMdaName = $portalMdaName ?: $snappedMdaName;


                    // =================================================================
                    // --- THE EXTRACTION ---
                    // =================================================================
                    
                    $this->info("📄 Parsing document structure for: " . $portalMdaName);
                    $htmlContent = $driver->getPageSource();
                    
                    // Passing $portalMdaName into the extraction method
                    $extractedRecords = $this->extractDataFromDocument($driver, $htmlContent, $portalMdaName);

                    foreach ($extractedRecords as $record) {
                        // These 3 are your "Primary Identity" (Database Unique Keys)
                        $ref = trim($record['reference_no']);
                        $mda = trim($record['mda_code']);
                        $sub = trim($record['subhead_code']);
                        
                        // These are your "Value Parameters"
                        $amt  = (float) $record['amount'];
                        $date = $record['release_date'];
                        $narr = trim($record['narration']);

                        // 1. CHECK MAIN LEDGER (Permanent Table)
                        // If it's already approved, we don't care about staging updates.
                        $inMainLedger = \App\Models\Release::where('reference_no', $ref)
                            ->where('mda_code', $mda)
                            ->where('subhead_code', $sub)
                            ->where('amount', $amt)
                            ->exists();

                        if ($inMainLedger) {
                            $this->info("🚫 Already Approved: Skipping Ref $ref");
                            continue;
                        }

                        // 2. CHECK STAGING TABLE (Searching ONLY by Unique Keys)
                        $existingStaged = \App\Models\ScrapedRelease::where('reference_no', $ref)
                            ->where('mda_code', $mda)
                            ->where('subhead_code', $sub)
                            ->first();

                        if ($existingStaged) {
                            // Now check all 6-7 parameters internally
                            $isExactMatch = (
                                (float)$existingStaged->amount === $amt &&
                                $existingStaged->release_date  === $date &&
                                $existingStaged->status        === $currentStatus &&
                                $existingStaged->narration     === $narr
                            );

                            if ($isExactMatch) {
                                $this->info("⏩ Skipping: Perfect match in staging for Ref $ref");
                                continue;
                            } 
                            
                            // If it exists but ANY parameter differs, update the existing row
                            $existingStaged->update([
                                'amount'       => $amt,
                                'release_date' => $date,
                                'status'       => $currentStatus,
                                'narration'    => $narr,
                                'mda_name'     => $record['mda_name'] ?? $existingStaged->mda_name
                            ]);
                            $this->warn("🔄 Updated Staging: Record refreshed for Ref $ref");
                            continue;
                        }

                        // 3. REGISTER NEW STAGING RECORD (Only if Ref/MDA/Sub is totally new)
                        \App\Models\ScrapedRelease::create([
                            'reference_no' => $ref,
                            'subhead_code' => $sub,
                            'mda_code'     => $mda,
                            'mda_name'     => $record['mda_name'] ?? 'N/A',
                            'release_date' => $date,
                            'amount'       => $amt,
                            'narration'    => $narr,
                            'status'       => $currentStatus
                        ]);
                        
                        $this->info("🆕 Recorded: Fresh release with " . count($extractedRecords) . " sub-records for Ref $ref");
                    }

                    // Navigate Back
                    $driver->navigate()->back();

                    // Wait for main table to reload
                    $driver->wait(60)->until(
                        WebDriverExpectedCondition::visibilityOfElementLocated(
                            WebDriverBy::tagName('table')
                        )
                    );
                    
                    // ADD THE SPACE HERE
                    $this->line('------------------------------------------------------------');
                    
                    //Uncomment the two lines below for manual filter reset
                    // $this->warn("✋ Manual Check: Reset filters if needed.");
                    // $this->confirm("Press Enter to continue...", true);
                    
                    usleep(500000);
                    
                } catch (\Exception $e) {
                    $this->error("❌ Error at $targetRef: " . $e->getMessage());
                    $driver->get('https://kteb.katsinastate.gov.ng/release-collection');
                    sleep(2);
                }
            }
            cache()->forget('scrape_progress');

        } finally {
            if ($driver) $driver->quit();
        }
    }

    /**
     * UNIFIED EXTRACTION LOGIC
     */
    private function extractDataFromDocument($driver, $html, $portalMdaName)
    {
        $tableRows = $driver->findElements(WebDriverBy::cssSelector('table tr'));
        $bodyText = $driver->findElement(WebDriverBy::tagName('body'))->getText();
        
        /**
         * SIMPLIFIED BULK CHECK
         * If row count > 2, it's a Bulk Release.
         */
        $isBulk = (count($tableRows) > 2);

        if ($isBulk) {
            $this->info("🔍 Format Detected: BULK (" . count($tableRows) . " rows found). Ignoring portal MDA.");
            return $this->scrapeBulkSalaryFormat($driver, $bodyText);
        }

        // Otherwise, treat as a single letter
        $this->info("🔍 Format Detected: STANDARD. Using portal MDA: $portalMdaName");
        return [$this->parseStandardLetter($bodyText, $portalMdaName)];
    }


    private function scrapeBulkSalaryFormat($driver, $fullText)
    {
        $stagedData = [];
        $currentMdaCode = null; 
        $currentMdaName = 'N/A';

        // 1. EXTRACT GLOBAL DATA
        $globalRef = "N/A";
        if (preg_match('/Our Ref:\s*([A-Z0-9\/\.\-]+)/i', strip_tags($fullText), $m)) {
            $globalRef = trim($m[1]);
        }

        $dateMatch = $this->regexMatch('/\d{1,2}(?:st|nd|rd|th)?\s+\w+,\s+\d{4}/i', $fullText);
        $globalDate = $this->parseDate($dateMatch);

        $globalNarration = "Special Release of Funds";
        $anchor = '(?:I\s*am\s+directed|enable|effect|facilitate|cater|settle|offset|being|for\s+the\s+implementation|payment\s+of|reimburse|payable|for\s+payment)';
        $anchorPattern = '/(' . $anchor . '.*?)<\/p>/is';

        if (preg_match($anchorPattern, $fullText, $m)) {
            $cleanedMatch = strip_tags($m[1]);
            $junkMarkers = ['CC:', 'This letter is copied', 'DIRECTOR OF BUDGET', 'The Vote of Charge'];
            foreach ($junkMarkers as $marker) {
                if (stripos($cleanedMatch, $marker) !== false) {
                    $parts = explode($marker, $cleanedMatch);
                    $cleanedMatch = $parts[0];
                }
            }
            $globalNarration = trim(preg_replace('/\s+/', ' ', $cleanedMatch));
        } else {
            $fallbackPattern = '/' . $anchor . '.*?(?=\n|$)/is';
            if (preg_match($fallbackPattern, $fullText, $m)) {
                $globalNarration = trim(preg_replace('/\s+/', ' ', strip_tags($m[0])));
            }
        }

        if (str_contains(strtolower($globalNarration), 'as follows')) {
            $globalNarration = rtrim($globalNarration, ':. ') . ':';
        } else {
            $globalNarration = rtrim($globalNarration, '.') . '.';
        }

        // 2. ITERATE TABLE ROWS
        sleep(2); 
        $tableRows = $driver->findElements(WebDriverBy::cssSelector('table tr'));

        foreach ($tableRows as $index => $row) {
            $cols = $row->findElements(WebDriverBy::tagName('td'));

            if (count($cols) < 3) {
                continue; 
            }

            $firstColRaw = trim($cols[0]->getText());
            $cleanCode = preg_replace('/[^0-9]/', '', $firstColRaw);

            if (empty($cleanCode)) {
                continue; 
            }

            // MDA HEADER (12 Digits)
            if (strlen($cleanCode) === 12) {
                $currentMdaCode = $cleanCode; 
                if (isset($cols[1])) {
                    $currentMdaName = trim($cols[1]->getText());
                } else {
                    $fullHeaderText = $cols[0]->getText();
                    $currentMdaName = str_contains($fullHeaderText, '-') 
                        ? trim(explode('-', $fullHeaderText)[1]) 
                        : trim(preg_replace('/[0-9]/', '', $fullHeaderText));
                }
                $this->warn("📂 Bulk Row MDA: $currentMdaName ($currentMdaCode)");
                continue; 
            }

            // UPDATED SUBHEAD ROW LOGIC (Handles 8, 9, 10, or 11 digits)
            $codeLength = strlen($cleanCode);

            if ($currentMdaCode && ($codeLength >= 8 && $codeLength <= 11)) {
                $subheadCode = $cleanCode;

                /**
                 * Fresh Problem Fix: 
                 * If it's 9 digits (Recurrent) or 11 digits (Capital) AND starts with 0,
                 * we strip that first zero to keep our database unique keys consistent.
                 */
                if (str_starts_with($subheadCode, '0') && ($codeLength === 9 || $codeLength === 11)) {
                    $subheadCode = substr($subheadCode, 1);
                    $this->info("🧹 Trimmed leading zero from subhead: $cleanCode -> $subheadCode");
                }

                $amount = 0;
                $maxRetries = 5;
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
                        'subhead_code' => $subheadCode, // Cleaned version
                        'amount'       => $amount
                    ];
                }
            }
        }

        return $stagedData;
    }
    
    private function parseStandardLetter($fullText, $portalMdaName)
    {
        $data = [
            'reference_no' => $this->regexMatch('/Our Ref:\s*([^\n\r]+)/i', $fullText),
            'release_date' => $this->parseDate($this->regexMatch('/\d{1,2}(?:st|nd|rd|th)?\s+\w+,\s+\d{4}/i', $fullText)),
            'amount'       => $this->cleanAmount($this->regexMatch('/\(N?([\d,]+(?:\.\d{2})?)\)/u', $fullText)),
            'narration'    => 'Standard Release',
            'mda_code'     => 'N/A',
            'mda_name'     => $portalMdaName, // <--- TRUSTED FROM PORTAL
            'subhead_code' => 'N/A'
        ];

        $anchor = '(?:I\s+(?:wish\s+.*?)\s*am\s+directed|enable|effect|facilitate|cater|settle|offset|being|for\s+the\s+implementation|payment\s+of|reimburse|payable|for\s+payment)';
        $anchorPattern = '/(' . $anchor . '.*?)<\/p>/is';

        if (preg_match($anchorPattern, $fullText, $matches)) {
            $data['narration'] = strip_tags($matches[1]);
        } else {
            $fallbackPattern = '/' . $anchor . '.*?(?=\n|$)/is';
            if (preg_match($fallbackPattern, $fullText, $matches)) {
                $data['narration'] = strip_tags($matches[0]);
            }
        }

        $data['narration'] = trim(preg_replace('/\s+/', ' ', $data['narration']));
        $data['narration'] = rtrim($data['narration'], '.') . '.';

        // 1. Updated Regex to allow up to 11 digits for the subhead
        if (preg_match('/(\d{12})\/(\d{8,11})/', $fullText, $m)) {
            $data['mda_code'] = $m[1];
            $rawSubhead = $m[2];
            $length = strlen($rawSubhead);

            // 2. Fresh Problem Fix: Normalize 9 and 11 digit codes starting with 0
            if (str_starts_with($rawSubhead, '0') && ($length === 9 || $length === 11)) {
                $data['subhead_code'] = substr($rawSubhead, 1);
                // Optional: $this->info("🧹 Normalized Standard Subhead: $rawSubhead -> " . $data['subhead_code']);
            } else {
                $data['subhead_code'] = $rawSubhead;
            }
        }

        return $data;
    }

    private function regexMatch($pattern, $text) {
        return preg_match($pattern, $text, $m) ? trim($m[1] ?? $m[0]) : '';
    }

    private function cleanAmount($val) {
        return (float) str_replace(['₦', ',', ' '], '', $val);
    }

    private function parseDate($dateStr) {
        if (!$dateStr) return now()->format('Y-m-d');
        $clean = preg_replace('/(\d+)(st|nd|rd|th)/i', '$1', $dateStr);
        return date('Y-m-d', strtotime($clean));
    }
}