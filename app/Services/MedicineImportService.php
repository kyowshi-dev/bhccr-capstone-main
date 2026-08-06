<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class MedicineImportService
{
    /**
     * Parse a CSV file of medicines and insert the valid rows.
     * Returns ['success_count' => int, 'errors' => list<string>, 'fatal_error' => ?string].
     */
    public static function import(string $path): array
    {
        $data = [];
        $errors = [];

        if (($handle = fopen($path, 'r')) === false) {
            return ['success_count' => 0, 'errors' => [], 'fatal_error' => null];
        }

        $header = fgetcsv($handle, 1000, ',');

        if (! $header || count($header) < 1) {
            fclose($handle);

            return ['success_count' => 0, 'errors' => [], 'fatal_error' => 'CSV file must have at least a name column.'];
        }

        $rowNumber = 1;
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $rowNumber++;

            if (count($row) === 0 || empty(trim($row[0]))) {
                continue;
            }

            $medicineData = [];
            foreach ($header as $index => $column) {
                $column = trim(strtolower($column));
                if (isset($row[$index])) {
                    $medicineData[$column] = trim($row[$index]);
                }
            }

            $medicineData['name'] = $medicineData['name'] ?? $medicineData['medicine_name'] ?? null;

            if (empty($medicineData['name'])) {
                $errors[] = "Row {$rowNumber}: Medicine name is required.";

                continue;
            }

            $existing = DB::table('medicines_lookup')
                ->where('name', $medicineData['name'])
                ->exists();

            if ($existing) {
                $errors[] = "Row {$rowNumber}: Medicine '{$medicineData['name']}' already exists.";

                continue;
            }

            if (! empty($medicineData['expiration_date'])) {
                $date = date('Y-m-d', strtotime($medicineData['expiration_date']));
                if ($date === '1970-01-01' || $date === false) {
                    $errors[] = "Row {$rowNumber}: Invalid expiration date format.";

                    continue;
                }
                $medicineData['expiration_date'] = $date;
            }

            $data[] = [
                'name' => $medicineData['name'],
                'generic_name' => $medicineData['generic_name'] ?? null,
                'strength' => $medicineData['strength'] ?? null,
                'form' => $medicineData['form'] ?? null,
                'manufacturer' => $medicineData['manufacturer'] ?? null,
                'expiration_date' => $medicineData['expiration_date'] ?? null,
                'is_active' => self::normalizeBoolean($medicineData['is_active'] ?? 'true'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        fclose($handle);

        $successCount = 0;
        if (! empty($data)) {
            try {
                DB::table('medicines_lookup')->insert($data);
                $successCount = count($data);
            } catch (\Exception $e) {
                $errors[] = 'Database error: '.$e->getMessage();
            }
        }

        return ['success_count' => $successCount, 'errors' => $errors, 'fatal_error' => null];
    }

    private static function normalizeBoolean(?string $value): ?bool
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = strtolower(trim($value));
        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return true;
    }
}
