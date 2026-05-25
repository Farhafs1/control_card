<?php

// 1. Pull in the composer autoloader for php-webdriver dependency
require __DIR__ . '/vendor/autoload.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

echo "🚀 [TEST START] Initializing standalone connection...\n";

// 2. Configure Chrome Headless Options matching your system
$options = new ChromeOptions();
$options->addArguments([
    '--headless=new',
    '--disable-gpu',
    '--no-sandbox',
    '--disable-setuid-sandbox',
    '--disable-dev-shm-usage',
    '--window-size=1920,1080',
    '--disable-blink-features=AutomationControlled',
]);

$capabilities = DesiredCapabilities::chrome();
$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
$capabilities->setCapability('pageLoadStrategy', 'eager');

try {
    // 3. Handshake directly with your open terminal window on Port 9515
    echo "🌐 Connecting to persistent ChromeDriver on http://127.0.0.1:9515...\n";
    
    $driver = RemoteWebDriver::create(
        'http://127.0.0.1:9515', 
        $capabilities, 
        5000, // 5 seconds connection timeout
        30000 // 30 seconds request timeout
    );

    echo "✅ Connected! Navigating to Katsina State Release Collection Portal...\n";
    $driver->get('https://kteb.katsinastate.gov.ng/release-collection');

    // 4. Wait for the JavaScript AJAX payload to mount real rows into DOM
    echo "⏳ Waiting for transaction table payload rows to load...\n";
    
    // Wait up to 15 seconds for a <td> that actually contains text and isn't just a "Loading..." placeholder
    $driver->wait(15)->until(function($driver) {
        $rows = $driver->findElements(WebDriverBy::cssSelector('table tbody tr'));
        if (count($rows) === 0) return false;
        
        // Check the text of the first row to ensure it's real data
        $firstRowText = strtolower($rows[0]->getText());
        if (empty($firstRowText) || str_contains($firstRowText, 'loading') || str_contains($firstRowText, 'no data')) {
            return false; // Keep waiting, it's still initializing
        }
        
        return true; // Real data has dropped!
    });
    
    // Give the DOM an extra half-second to settle the remaining rows
    usleep(500000); 


    // 5. Scrape first few rows to confirm data extraction works perfectly
    $rows = $driver->findElements(WebDriverBy::cssSelector('table tbody tr'));
    $totalRowsFound = count($rows);
    echo "📸 Snapshot Complete! Total rows visible on page: {$totalRowsFound}\n\n";
    echo str_repeat("-", 80) . "\n";
    echo sprintf("%-15s | %-45s | %-15s\n", "STATUS/DATE", "MDA DESCRIPTION", "REFERENCE NO");
    echo str_repeat("-", 80) . "\n";

    // Inspect the top 5 records to prove accuracy
    $sampleCount = min($totalRowsFound, 5);
    for ($i = 0; $i < $sampleCount; $i++) {
        $cells = $rows[$i]->findElements(WebDriverBy::tagName('td'));
        if (count($cells) < 6) continue;

        $statusOrDate = trim($cells[0]->getText());
        $mdaName      = trim($cells[3]->getText() ?: $cells[4]->getText());
        $referenceNo  = trim($cells[5]->getText() ?: $cells[4]->getText());

        // Clean up string limits for console printing spacing
        if (strlen($mdaName) > 42) {
            $mdaName = substr($mdaName, 0, 39) . "...";
        }

        echo sprintf("%-15s | %-45s | %-15s\n", $statusOrDate, $mdaName, $referenceNo);
    }
    echo str_repeat("-", 80) . "\n";
    echo "\n🎉 [SUCCESS] Data matrix extracted accurately without locking up process pipes.\n";

} catch (\Exception $e) {
    echo "\n❌ [TEST FAILED]: " . $e->getMessage() . "\n";
    echo "Verify your background console window running ChromeDriver on port 9515 is still alive.\n";
} finally {
    if (isset($driver)) {
        // Disconnect safely. This closes the hidden window but keeps the background terminal runner ALIVE.
        $driver->close();
        echo "🔌 Session detached smoothly. Server window remains active.\n";
    }
}