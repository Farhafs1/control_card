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

    public $csvFile;

    /**
     * Clear all expenditure data (Destructive)
     */
    public function truncateExpenditure()
    {
        DB::statement('PRAGMA foreign_keys = OFF;');
        Release::truncate();
        PendingVerification::truncate();
        DB::statement('PRAGMA foreign_keys = ON;');

        session()->flash('message', 'Expenditure ledger and pending flags have been completely cleared.');
        return redirect(request()->header('Referer'));
    }

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
                
                $mdaCodeCSV = preg_replace('/[^0-9]/', '', trim($record['mda_code'] ?? ''));
                $subheadCodeCSV = preg_replace('/[^0-9]/', '', trim($record['subhead_code'] ?? ''));

                if (empty($mdaCodeCSV) && empty($subheadCodeCSV)) continue;

                // Lookup Subhead and MDA to get IDs
                $subhead = Subhead::byCodes($mdaCodeCSV, $subheadCodeCSV)->first();
                
                if (!$subhead) {
                    throw new \Exception("Budget line '$subheadCodeCSV' not found for MDA '$mdaCodeCSV' at row $currentRow. Please ensure MDAs and Subheads are registered first.");
                }

                $rawAmount = (float) str_replace([',', ' ', '₦'], '', $record['amount'] ?? 0);
                $dateValue = trim($record['release_date'] ?? $record['date'] ?? '');
                
                try {
                    $cleanDate = Carbon::createFromFormat('d/m/Y', $dateValue)->format('Y-m-d');
                } catch (\Exception $e) {
                    try {
                        $cleanDate = Carbon::parse($dateValue)->format('Y-m-d');
                    } catch (\Exception $finalError) {
                        throw new \Exception("Invalid date format at row $currentRow. Use DD/MM/YYYY.");
                    }
                }

                $refNo = trim($record['reference_no'] ?? $record['reference'] ?? '');

                // Check for duplicates in main ledger
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

    public function confirmItem($id)
    {
        $pending = PendingVerification::find($id);

        if ($pending) {
            $mda = \App\Models\Mda::where('mda_code', $pending->mda_code)->first();
            $subhead = \App\Models\Subhead::where('subhead_code', $pending->subhead_code)
                                        ->where('mda_code', $pending->mda_code)
                                        ->first();

            try {
                // Attempt to move to main ledger
                \App\Models\Release::create([
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

            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // Instead of crashing, we leave it in pending but mark it
                $pending->update(['narration' => $pending->narration . ' [IDENTICAL DUPLICATE DETECTED]']);
                
                session()->flash('error', 'This record is an exact duplicate of an existing entry. It has been kept in the pending list for your analysis.');
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
        /**
         * AUTO-FIX: Link any existing records that are missing mda_id
         */
        Release::whereNull('mda_id')->chunk(100, function($releases) {
            foreach($releases as $release) {
                $mda = Mda::where('mda_code', $release->mda_code)->first();
                if ($mda) {
                    $release->update(['mda_id' => $mda->id]);
                }
            }
        });

        return view('livewire.admin.expenditure-upload', [
            'recentReleases' => Release::with(['mda'])->latest()->take(10)->get(),
            'pendingItems' => PendingVerification::with(['mda'])->get()
        ])->layout('layouts.app');
    }
}