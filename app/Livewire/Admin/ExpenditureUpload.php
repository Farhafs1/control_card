<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Release;
use App\Models\Subhead;
use App\Models\Mda;
use App\Models\PendingVerification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExpenditureUpload extends Component
{
    use WithFileUploads;

    // CSV Upload Property
    public $csvFile;

    // Single Entry Properties
    public $mda_id, $subhead_id, $release_date, $reference_no, $amount, $narration;
    public $subheads = []; // Holds subheads for the selected MDA

    /**
     * Listener for MDA selection change
     */
    public function updatedMdaId($value)
    {
        if ($value) {
            $this->subheads = \App\Models\Subhead::where('mda_id', $value)
                ->orderBy('subhead_code')
                ->get();
            
            // Convert to a plain array to ensure JS can read it perfectly
            $subheadArray = $this->subheads->map(function($sh) {
                return [
                    'id' => $sh->id,
                    'text' => $sh->subhead_code . ' - ' . $sh->description
                ];
            })->toArray();

            // Dispatch the data directly in the event
            $this->dispatch('mda-updated', data: $subheadArray); 
        } else {
            $this->subheads = [];
            $this->dispatch('mda-updated', data: []);
        }

        $this->subhead_id = null;
    }

    /**
     * Save a single manual entry
     */
    public function saveSingleEntry()
    {
        // TEMPORARY: Check if the data is actually arriving
        //dd($this->all());
        $this->validate([
            'mda_id' => 'required|exists:mdas,id',
            'subhead_id' => 'required|exists:subheads,id',
            'release_date' => 'required|date',
            'reference_no' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'narration' => 'nullable|string|max:2000',
        ]);

        $subhead = Subhead::find($this->subhead_id);
        $mda = Mda::find($this->mda_id);

        $data = [
            'mda_id'       => $mda->id,
            'subhead_id'   => $subhead->id,
            'mda_code'     => $mda->mda_code,
            'subhead_code' => $subhead->subhead_code,
            'release_date' => $this->release_date,
            'reference_no' => trim($this->reference_no),
            'amount'       => (float) $this->amount,
            // Saves your manual note, or uses the description if you left it empty
            'narration'    => trim($this->narration) ?: $subhead->description,
        ];

        // Check for duplicates (Composite Unique Index Check)
        $isDuplicate = Release::where([
            'mda_id'       => $data['mda_id'],
            'subhead_id'   => $data['subhead_id'],
            'release_date' => $data['release_date'],
            'amount'       => $data['amount'],
            'reference_no' => $data['reference_no']
        ])->exists();

        if ($isDuplicate) {
            PendingVerification::create($data);
            session()->flash('message', 'Duplicate detected: Entry moved to Verification Queue.');
        } else {
            Release::create($data);
            session()->flash('message', 'Record successfully saved to Ledger.');
        }

        $this->reset(['mda_id', 'subhead_id', 'release_date', 'reference_no', 'amount', 'narration', 'subheads']);
    }

    /**
     * Batch Upload Logic
     */
    public function processImport()
    {
        $this->validate([
            'csvFile' => 'required|max:5120|mimes:csv,txt',
        ]);

        $currentRow = 1; 
        $handle = null;
        $duplicatesFound = 0;

        try {
            $path = $this->csvFile->getRealPath();
            $content = file_get_contents($path);
            
            $bom = pack('H*','EFBBBF');
            $content = preg_replace("/^$bom/", '', $content);
            
            $handle = fopen('php://temp', 'r+');
            fwrite($handle, $content);
            rewind($handle);
            
            $rawHeader = fgetcsv($handle);
            if (!$rawHeader) throw new \Exception("The CSV file is empty.");

            $header = array_map(fn($h) => strtolower(str_replace([' ', '-'], '_', trim($h))), $rawHeader);
            
            DB::beginTransaction();
            
            $rowCount = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $currentRow++;
                if (empty(array_filter($row))) continue;

                $record = array_combine($header, array_pad($row, count($header), ''));
                
                // 1. Clean the codes
                $mdaCodeCSV = preg_replace('/[^0-9]/', '', trim($record['mda_code'] ?? ''));
                $subheadCodeCSV = preg_replace('/[^0-9]/', '', trim($record['subhead_code'] ?? ''));

                if (empty($mdaCodeCSV) && empty($subheadCodeCSV)) continue;

                // 2. Lookup the Subhead (The link to the Budget)
                $subhead = Subhead::byCodes($mdaCodeCSV, $subheadCodeCSV)->first();

                if (!$subhead) {
                    throw new \Exception("Budget line '$subheadCodeCSV' not found for MDA '$mdaCodeCSV' at row $currentRow.");
                }

                // 3. DEFINE ALL VARIABLES FIRST (This prevents the "Undefined" error)
                $rawAmount = (float) str_replace([',', ' ', '₦'], '', $record['amount'] ?? 0);
                $dateValue = trim($record['release_date'] ?? $record['date'] ?? '');
                $refNo     = trim($record['reference_no'] ?? $record['reference'] ?? '');

                // 4. Robust Date Parsing
                try {
                    // If the date contains a "-", it's likely YYYY-MM-DD (your backup format)
                    if (str_contains($dateValue, '-')) {
                        $cleanDate = Carbon::parse($dateValue)->format('Y-m-d');
                    } else {
                        // Otherwise assume DD/MM/YYYY
                        $cleanDate = Carbon::createFromFormat('d/m/Y', $dateValue)->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    try {
                        $cleanDate = Carbon::parse($dateValue)->format('Y-m-d');
                    } catch (\Exception $lastResort) {
                        throw new \Exception("Invalid date format '$dateValue' at row $currentRow.");
                    }
                }

                // 5. NOW perform the Duplicate Check (Variables are now safe to use)
                $isDuplicate = Release::where([
                    'mda_id' => $subhead->mda_id,
                    'subhead_id' => $subhead->id,
                    'release_date' => $cleanDate,
                    'amount' => $rawAmount,
                    'reference_no' => $refNo
                ])->exists();

                $data = [
                    'mda_id' => $subhead->mda_id,
                    'subhead_id' => $subhead->id,
                    'mda_code' => $subhead->mda_code,
                    'subhead_code' => $subhead->subhead_code,
                    'release_date' => $cleanDate,
                    'reference_no' => $refNo,
                    'amount' => $rawAmount,
                    'narration' => trim($record['narration'] ?? ''),
                ];

                if ($isDuplicate) {
                    PendingVerification::create($data);
                    $duplicatesFound++;
                } else {
                    Release::create($data);
                    $rowCount++;
                }
            }
            
            DB::commit();
            fclose($handle);
            
            $msg = "Successfully imported $rowCount records.";
            if ($duplicatesFound > 0) $msg .= " Found $duplicatesFound duplicates requiring verification.";
            
            session()->flash('message', $msg);
            $this->reset('csvFile');
            return redirect(request()->header('Referer'));

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            if ($handle) fclose($handle);
            session()->flash('error', "Error: " . $e->getMessage());
        }
    }

    public function truncateExpenditure()
    {
        DB::statement('PRAGMA foreign_keys = OFF;');
        Release::truncate();
        PendingVerification::truncate();
        DB::statement('PRAGMA foreign_keys = ON;');

        session()->flash('message', 'Expenditure ledger and pending flags have been completely cleared.');
        return redirect(request()->header('Referer'));
    }

    public function confirmItem($id)
    {
        $pending = PendingVerification::find($id);

        if ($pending) {
            $mda = Mda::where('mda_code', $pending->mda_code)->first();
            $subhead = Subhead::where('subhead_code', $pending->subhead_code)
                                ->where('mda_code', $pending->mda_code)
                                ->first();

            try {
                Release::create([
                    'mda_id'         => $mda->id,
                    'subhead_id'     => $subhead?->id,
                    'mda_code'       => $pending->mda_code,
                    'subhead_code'   => $pending->subhead_code,
                    'release_date'   => $pending->release_date,
                    'reference_no'   => $pending->reference_no,
                    'amount'         => $pending->amount,
                    'narration'      => $pending->narration,
                ]);

                $pending->delete();
                session()->flash('message', 'Record confirmed and moved to ledger.');

            } catch (\Exception $e) {
                $pending->update(['narration' => $pending->narration . ' [IDENTICAL DUPLICATE DETECTED]']);
                session()->flash('error', 'This record is an exact duplicate of an existing entry.');
            }
        }
    }

    public function discardItem($id)
    {
        $pending = PendingVerification::find($id);
        if ($pending) {
            $pending->delete();
            session()->flash('message', 'Duplicate record discarded.');
        }
    }

    public function render()
    {
        // Periodic linking of orphaned records (if any)
        Release::whereNull('mda_id')->chunk(100, function($releases) {
            foreach($releases as $release) {
                $mda = Mda::where('mda_code', $release->mda_code)->first();
                if ($mda) {
                    $release->update(['mda_id' => $mda->id]);
                }
            }
        });

        return view('livewire.admin.expenditure-upload', [
            'mdas' => Mda::orderBy('mda_code')->get(),
            'recentReleases' => Release::with(['mda'])->latest()->take(10)->get(),
            'pendingItems' => PendingVerification::with(['mda'])->get()
        ])->layout('layouts.app');
    }
}