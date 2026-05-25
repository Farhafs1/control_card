<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

class ScraperController extends Controller
{
    public function sync(Request $request)
    {
        $limit = $request->query('limit', 10);
        
        // Define the full path to your project and chromedriver
        $projectPath = 'C:\Budget\Softwares\bin_card\control_card';
        $chromeDriverPath = $projectPath . '\chromedriver.exe';

        // 1. Check if ChromeDriver is running; if not, start it SILENTLY & DETACHED
        exec("tasklist | findstr chromedriver.exe", $output);
        if (empty($output)) {
            // "" is a dummy window title required by the Windows start command when quotes are used in paths
            // > NUL 2>&1 discards output and breaks the stream-binding lock on PHP
            pclose(popen("start /B \"\" \"$chromeDriverPath\" --port=9515 > NUL 2>&1", "r"));
        }

        // 2. Start the Queue Worker SILENTLY & DETACHED
        pclose(popen("start /B \"\" php artisan queue:work --stop-when-empty > NUL 2>&1", "r"));

        // 3. Trigger the Scraper
        Artisan::queue('budget:scrape', [
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
}