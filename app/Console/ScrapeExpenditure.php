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
use Carbon\Carbon; // Added for date formatting

class ScrapeExpenditure extends Command
{
    protected $signature = 'budget:scrape {--limit=5} {--user=} {--pass=}';

    public function handle()
    {
        $user = $this->option('user');
        $pass = $this->option('pass');
        $limit = (int) $this->option('limit');

        $this->info("🚀 Initializing Manual Engine...");

        $options = new ChromeOptions();
        $options->addArguments(['--disable-gpu', '--no-sandbox', '--window-size=1920,1080']);
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        try {
            $driver = RemoteWebDriver::create('http://127.0.0.1:9515', $capabilities);
            
            // --- 1. LOGIN (Existing functional logic) ---
            $this->info("Step 1: Navigating to Login...");
            $driver->get('https://kteb.katsinastate.gov.ng/login');
            
            $driver->wait(30)->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::name('username'))
            );
            
            $this->info("Entering credentials...");
            $driver->findElement(WebDriverBy::name('username'))->sendKeys($user);
            $driver->findElement(WebDriverBy::name('password'))->sendKeys($pass);
            
            sleep(1); 

            $this->info("Clicking Sign in...");
            try {
                $loginBtn = $driver->findElement(WebDriverBy::xpath("//button[contains(., 'Sign in')]"));
                $loginBtn->click();
            } catch (\Exception $e) {
                $this->warn("Button click failed, trying ENTER key fallback...");
                $driver->findElement(WebDriverBy::name('password'))->sendKeys([WebDriverKeys::ENTER]);
            }
            
            // --- 2. NAVIGATION (Existing functional logic) ---
            $this->info("Step 2: Starting Navigation...");

            $parentMenu = $driver->wait(15)->until(
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
                $driver->wait(10)->until(
                    WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::xpath($xpath))
                );
                $subMenuItem = $driver->findElement(WebDriverBy::xpath($xpath));
                $driver->executeScript("arguments[0].click();", [$subMenuItem]);
            } catch (\Exception $e) {
                $this->error("Sub-menu click failed: " . $e->getMessage());
                $driver->executeScript(
                    "document.evaluate(\"(//li[contains(., 'Release Processing')])[last()]\", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.click();"
                );
            }

            // --- 3. SCRAPING THE TABLE ---
            $this->info("Step 3: Waiting for Table Data...");
            $driver->wait(20)->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('table'))
            );
            
            $driver->wait(10)->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::xpath("//table/tbody/tr"))
            );

            sleep(5); 

            $processedCount = 0;

            for ($i = 1; $i <= $limit; $i++) {
                try {
                    $rowXpath = "(//table/tbody/tr)[$i]";
                    $row = $driver->findElement(WebDriverBy::xpath($rowXpath));
                    $refNo = $row->findElement(WebDriverBy::xpath("./td[1]"))->getText();
                    
                    if (empty($refNo)) continue;

                    $this->info("Scraping Ref: " . $refNo);

                    $viewBtn = $row->findElement(WebDriverBy::xpath(".//button[contains(., 'View')]"));
                    $driver->executeScript("arguments[0].scrollIntoView(true);", [$viewBtn]);
                    $viewBtn->click();

                    $previewBtnXpath = "//button[contains(@class, 'btn-link') and contains(text(), 'Preview Document')]";
                    $previewBtn = $driver->wait(15)->until(
                        WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::xpath($previewBtnXpath))
                    );

                    $driver->executeScript("arguments[0].scrollIntoView({block: 'center'});", [$previewBtn]);
                    usleep(500000); 
                    $driver->executeScript("arguments[0].click();", [$previewBtn]);

                    $driver->wait(20)->until(
                        WebDriverExpectedCondition::presenceOfElementLocated(
                            WebDriverBy::xpath("//div[@role='dialog'] | //div[contains(@class, 'MuiPaper-root')]")
                        )
                    );

                    // --- EXTRACTION LOGIC ---
                    $letterContainer = $driver->wait(10)->until(
                        WebDriverExpectedCondition::presenceOfElementLocated(
                            WebDriverBy::xpath("//div[contains(@style, 'background-color: white')]")
                        )
                    );
                    $fullText = $letterContainer->getText();

                    $data = [
                        'reference' => '', 'date' => '', 'amount' => 0,
                        'narration' => '', 'mda_code' => '', 'subhead' => ''
                    ];

                    if (preg_match('/Our Ref:\s*([^\n\r]+)/i', $fullText, $matches)) $data['reference'] = trim($matches[1]);
                    if (preg_match('/\d{1,2}(st|nd|rd|th)\s+\w+,\s+\d{4}/i', $fullText, $matches)) $data['date'] = $matches[0];
                    if (preg_match('/\(N?([\d,]+\.\d{2})\)/u', $fullText, $matches)) $data['amount'] = (float) str_replace(',', '', $matches[1]);
                    
                    $narPattern = '/\b(to enable|to effect|to facilitate|being|to cater|for the implementation)\b.*?(?=\.|\n|$)/i';
                    if (preg_match($narPattern, $fullText, $matches)) $data['narration'] = trim($matches[0]);

                    if (preg_match('/(\d{12})\/(\d{8,10})/', $fullText, $matches)) {
                        $data['mda_code'] = $matches[1];
                        $data['subhead']  = $matches[2];
                    }

                    // --- FIXED: DATABASE SAVING ---
                    if (!empty($data['reference'])) {
                        ScrapedRelease::updateOrCreate(
                            ['reference_no' => $data['reference']],
                            [
                                'mda_code'     => $data['mda_code'],
                                'subhead_code' => $data['subhead'],
                                'release_date' => $data['date'] ? Carbon::parse($data['date'])->format('Y-m-d') : now()->format('Y-m-d'),
                                'amount'       => $data['amount'],
                                'narration'    => $data['narration'],
                                'status'       => 'staged'
                            ]
                        );
                        $this->info("✅ Saved to database: " . $data['reference']);
                        $processedCount++;
                    }

                    // Close the Document View
                    $driver->getKeyboard()->sendKeys([WebDriverKeys::ESCAPE]);
                    sleep(2);

                } catch (\Exception $e) {
                    $this->warn("Row $i skipped: " . $e->getMessage());
                    $driver->getKeyboard()->sendKeys([WebDriverKeys::ESCAPE]);
                    continue;
                }
            }

            $this->info("🎉 Done! $processedCount records synced.");

        } catch (\Exception $e) {
            $this->error("❌ Critical Failure: " . $e->getMessage());
            if (isset($driver)) $driver->quit();
        }
    }
}