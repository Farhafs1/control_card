<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ScrapedRelease;
use App\Models\Release;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DataExtraction extends Component
{
    // Scraper UI Parameters
    public $startDate;
    public $endDate;
    public $batchLimit = 50;
    
    // Connection Parameters
    public $scraperUrl;
    public $scraperUser;
    public $scraperPass;
    public $requireLogin = true;

    // UI States
    public $isCrawling = false;
    public $showSettings = false;

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');

        $settings = Setting::first();
        if ($settings) {
            $this->scraperUrl = $settings->scraper_url;
            $this->scraperUser = $settings->scraper_username;
            $this->scraperPass = $settings->scraper_password;
            $this->requireLogin = (bool) $settings->require_login;
        }
    }

    public function startExtraction()
    {
        $this->validate([
            'startDate'   => 'required|date',
            'endDate'     => 'required|date|after_or_equal:startDate',
            'batchLimit'  => 'required|integer|min:1|max:200',
            'scraperUrl'  => 'required|url',
            'scraperUser' => 'required_if:requireLogin,true',
            'scraperPass' => 'required_if:requireLogin,true',
        ]);

        $this->isCrawling = true;

        try {
            Artisan::call('budget:scrape', [
                '--start'   => $this->startDate,
                '--end'     => $this->endDate,
                '--limit'   => $this->batchLimit,
                '--url'     => $this->scraperUrl,
                '--user'    => $this->scraperUser,
                '--pass'    => $this->scraperPass,
                '--doLogin' => $this->requireLogin ? 1 : 0,
            ]);

            $this->dispatch('swal', [
                'title' => 'Sync Successful',
                'text'  => 'Records are now waiting in the staging area.',
                'icon'  => 'success',
                'timer' => 3000
            ]);

        } catch (\Exception $e) {
            Log::error("Scraper Failure: " . $e->getMessage());
            $this->dispatch('swal', [
                'title' => 'Connection Failed',
                'text'  => 'Could not reach external portal. Verify your credentials.',
                'icon'  => 'error',
                'timer' => 3000
            ]);
        } finally {
            $this->isCrawling = false;
        }
    }

    public function saveSettings()
    {
        Setting::updateOrCreate(
            ['id' => 1],
            [
                'scraper_url'      => $this->scraperUrl,
                'scraper_username' => $this->scraperUser,
                'scraper_password' => $this->scraperPass,
                'require_login'    => $this->requireLogin,
            ]
        );

        $this->dispatch('swal', [
            'title' => 'Configuration Saved',
            'icon'  => 'success',
            'timer' => 2000,
            'toast' => true,
            'position' => 'top-end'
        ]);
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