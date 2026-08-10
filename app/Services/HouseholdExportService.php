<?php

namespace App\Services;

use Illuminate\Support\Collection;

final class HouseholdExportService
{
    /**
     * Build CSV content from household records and member counts.
     *
     * @param  Collection<int, mixed>  $households
     * @param  array<int, int>  $memberCounts
     */
    public static function csvContent(Collection $households, array $memberCounts): string
    {
        $output = fopen('php://temp', 'w');
        self::writeCsvRows($output, $households, $memberCounts);
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Write CSV rows to an open file handle (used for streaming).
     *
     * @param  resource  $handle
     * @param  Collection<int, mixed>  $households
     * @param  array<int, int>  $memberCounts
     */
    public static function writeCsvRows($handle, Collection $households, array $memberCounts): void
    {
        fputcsv($handle, ['ID', 'Zone', 'Family Name', 'Contact Number', 'Registered Date', 'Member Count']);

        foreach ($households as $household) {
            fputcsv($handle, [
                $household->id,
                $household->zone_number,
                $household->family_name_head,
                $household->contact_number ?? '',
                $household->created_at,
                $memberCounts[$household->id] ?? 0,
            ]);
        }
    }
}
