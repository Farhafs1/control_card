<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

public function sync(Request $request)
{
    $limit = $request->query('limit', 10);
    
    // Define the full path to your project and chromedriver
    $projectPath = 'C:\Budget\Softwares\bin_card\control_card';
    $chromeDriverPath = $projectPath . '\chromedriver.exe';

    // 1. Check if ChromeDriver is running; if not, start it SILENTLY
    exec("tasklist | findstr chromedriver.exe", $output);
    if (empty($output)) {
        // We use the full path so there is no confusion
        // /B makes it run in the background with no terminal window
        pclose(popen("start /B $chromeDriverPath --port=9515", "r"));
    }

    // 2. Start the Queue Worker SILENTLY
    // --stop-when-empty means it will close itself when the job is done
    pclose(popen("start /B php artisan queue:work --stop-when-empty", "r"));

    // 3. Trigger the Scraper
    \Illuminate\Support\Facades\Artisan::queue('budget:scrape', [
        '--limit' => $limit
    ]);

    return response()->json(['status' => 'Sync Initiated']);
}

public function getProgress()
{
    // This allows the frontend to "ask" how far the scraper has gone
    return response()->json(cache()->get('scrape_progress', [
        'percent' => 0, 
        'status' => 'Waiting...'
    ]));
}