<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IcdDiagnosisSeeder extends Seeder
{
    private const CHUNK_SIZE = 500;

    public function run(): void
    {
        $path = config('bhcis.icd_sql_path')
            ?: storage_path('app/icd102019syst_codes.sql');

        if (! is_file($path) || ! is_readable($path)) {
            $this->command?->warn("ICD SQL file not found or not readable: {$path}. Copy icd102019syst_codes.sql to storage/app/ or set BHCIS_ICD_SQL_PATH in .env");

            return;
        }

        $rows = $this->parseIcdSqlFile($path);
        $this->command?->info('Parsed '.count($rows).' ICD-10 diagnosis codes.');

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            foreach ($chunk as $row) {
                DB::table('diagnosis_lookup')->updateOrInsert(
                    ['diagnosis_code' => $row['diagnosis_code']],
                    [
                        'diagnosis_name' => $row['diagnosis_name'],
                        'category' => $row['category'],
                    ]
                );
            }
        }

        $this->command?->info('Diagnosis lookup import finished.');
    }

    /**
     * @return array<int, array{diagnosis_code: string, diagnosis_name: string, category: string|null}>
     */
    private function parseIcdSqlFile(string $path): array
    {
        $rows = [];
        $inValues = false;
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return $rows;
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === 'VALUES') {
                    $inValues = true;

                    continue;
                }
                if (! $inValues || $line === '') {
                    continue;
                }
                if (preg_match("/^\s*\(\s*'((?:[^']|'')*)'\s*\)\s*[,;]?\s*$/", $line, $m)) {
                    $raw = str_replace("''", "'", $m[1]);
                    $parsed = $this->parseIcdLine($raw);
                    if ($parsed !== null) {
                        $rows[] = $parsed;
                    }
                }
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /**
     * @return array{diagnosis_code: string, diagnosis_name: string, category: string|null}|null
     */
    private function parseIcdLine(string $raw): ?array
    {
        $parts = explode(';', $raw);
        if (count($parts) < 9) {
            return null;
        }
        $level = $parts[0];
        if ($level !== '4') {
            return null;
        }
        $diagnosisCode = trim($parts[5] ?? '');
        $diagnosisName = trim($parts[8] ?? '');
        if ($diagnosisCode === '' || $diagnosisName === '') {
            return null;
        }
        $category = isset($parts[9]) && trim($parts[9]) !== '' ? trim($parts[9]) : null;

        return [
            'diagnosis_code' => $diagnosisCode,
            'diagnosis_name' => $diagnosisName,
            'category' => $category,
        ];
    }
}
