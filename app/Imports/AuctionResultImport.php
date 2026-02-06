<?php

namespace App\Imports;

use App\Services\AuctionResultService;
use App\Models\SecurityMasterData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Log;

class AuctionResultImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $service;
    protected $createdBy;
    protected $authoriserId;

    public function __construct(
        AuctionResultService $service,
        int $createdBy,
        int $authoriserId
    ) {
        $this->service = $service;
        $this->createdBy = $createdBy;
        $this->authoriserId = $authoriserId;
    }

    public function collection(Collection $rows)
    {
        $rowNumber = 1; 
        $successCount = 0;
        $errorCount = 0;

        // Pre-fetch securities to minimize queries? 
        // Or just query individually (simpler for now if list is not huge).
        // Let's optimize slightly by caching names.
        // Assuming security names are unique or enough to identify.

        foreach ($rows as $row) {
            $rowNumber++;
            try {
                $this->processRow($row, $rowNumber);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Auction Result Import Error Row {$rowNumber}: " . $e->getMessage());
            }
        }

        Log::info("Auction Import completed: {$successCount} successful, {$errorCount} errors.");
    }

    protected function processRow($row, $rowNumber)
    {
        // 1. Identify Security
        $security = null;
        if (!empty($row['security_id'])) {
             $security = \App\Models\SecurityMasterData::find($row['security_id']);
        } elseif (!empty($row['isin'])) {
             // Assuming security_master_data has isin or equivalent field, or we stick to checking name.
             // SecurityMasterData has 'security_name'. ISIN might not be there directly based on migration? 
             // Migration 2026_02_03_113336 shows only security_name. 
             // Let's assume security_name matches.
             $security = \App\Models\SecurityMasterData::where('security_name', $row['isin'])->first();
        } elseif (!empty($row['security_name'])) {
            $security = \App\Models\SecurityMasterData::where('security_name', $row['security_name'])->first();
        }

        if (!$security) {
            throw new \Exception("Security not found for: " . ($row['security_id'] ?? $row['security_name'] ?? $row['isin'] ?? 'Unknown'));
        }

        // 2. Prepare Data
        $data = [
            'security_id' => $security->id,
            'created_by' => $this->createdBy,
            'authoriser_id' => $this->authoriserId,
            
            'auction_number' => $row['auction_number'] ?? null,
            'auction_date' => $this->parseDate($row['auction_date'] ?? null),
            'value_date' => $this->parseDate($row['value_date'] ?? null),
            'tenor_days' => $row['tenor'] ?? $row['tenor_days'] ?? 0,
            
            'amount_offered' => $row['amount_offered'] ?? 0,
            'amount_subscribed' => $row['amount_subscribed'] ?? 0,
            'amount_allotted' => $row['amount_allotted'] ?? 0,
            'amount_sold' => $row['amount_sold'] ?? 0,
            'non_competitive_sales' => $row['non_competitive_sales'] ?? 0,
            
            'stop_rate' => $row['stop_rate'] ?? 0,
            'marginal_rate' => $row['marginal_rate'] ?? null,
            'true_yield' => $row['true_yield'] ?? null,
            
            'remarks' => $row['remarks'] ?? null,
            'status' => $row['status'] ?? 'Completed',
        ];

        // 3. Create Request
        $this->service->createRequest($data);
    }

    private function parseDate($date)
    {
        if (empty($date)) return now(); // Or null? Required field.
        try {
            if (is_numeric($date)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d');
            }
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return now();
        }
    }
}
