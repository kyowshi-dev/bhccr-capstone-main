<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicineController extends Controller
{
    public function index()
    {
        if (! auth()->user()->hasPermission('medicines')) {
            abort(403, 'Unauthorized');
        }

        $medicines = DB::table('medicines_lookup')
            ->orderBy('medicine_name')
            ->paginate(25)
            ->withQueryString();

        return view('medicines.index', [
            'medicines' => $medicines,
        ]);
    }

    public function create()
    {
        if (! auth()->user()->hasPermission('medicines')) {
            abort(403, 'Unauthorized');
        }

        return view('medicines.create');
    }

    public function store(Request $request)
    {
        if (! auth()->user()->hasPermission('medicines')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'medicine_name' => ['required', 'string', 'max:255', 'unique:medicines_lookup,medicine_name'],
            'category' => ['nullable', 'string', 'max:255'],
            'expiration_date' => ['nullable', 'date'],
        ]);

        DB::table('medicines_lookup')->insert([
            'medicine_name' => $validated['medicine_name'],
            'category' => $validated['category'] ?? null,
            'expiration_date' => $validated['expiration_date'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('medicines.index')
            ->with('success', 'Medicine added successfully.');
    }

    public function import(Request $request)
    {
        if (! auth()->user()->hasPermission('medicines')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $data = [];
        $errors = [];
        $successCount = 0;

        if (($handle = fopen($path, 'r')) !== false) {
            $header = fgetcsv($handle, 1000, ',');

            // Validate header
            $expectedHeaders = ['medicine_name', 'category', 'expiration_date'];
            if (! $header || count($header) < 1) {
                return redirect()
                    ->route('medicines.index')
                    ->with('error', 'CSV file must have at least a medicine_name column.');
            }

            $rowNumber = 1;
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $rowNumber++;

                if (count($row) === 0 || empty(trim($row[0]))) {
                    continue; // Skip empty rows
                }

                $medicineData = [];
                foreach ($header as $index => $column) {
                    $column = trim(strtolower($column));
                    if (isset($row[$index])) {
                        $medicineData[$column] = trim($row[$index]);
                    }
                }

                // Validate required fields
                if (empty($medicineData['medicine_name'])) {
                    $errors[] = "Row {$rowNumber}: Medicine name is required.";

                    continue;
                }

                // Check for duplicates
                $existing = DB::table('medicines_lookup')
                    ->where('medicine_name', $medicineData['medicine_name'])
                    ->exists();

                if ($existing) {
                    $errors[] = "Row {$rowNumber}: Medicine '{$medicineData['medicine_name']}' already exists.";

                    continue;
                }

                // Validate expiration date if provided
                if (! empty($medicineData['expiration_date'])) {
                    $date = date('Y-m-d', strtotime($medicineData['expiration_date']));
                    if ($date === '1970-01-01' || $date === false) {
                        $errors[] = "Row {$rowNumber}: Invalid expiration date format.";

                        continue;
                    }
                    $medicineData['expiration_date'] = $date;
                }

                $data[] = [
                    'medicine_name' => $medicineData['medicine_name'],
                    'category' => $medicineData['category'] ?? null,
                    'expiration_date' => $medicineData['expiration_date'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            fclose($handle);
        }

        // Insert valid data
        if (! empty($data)) {
            try {
                DB::table('medicines_lookup')->insert($data);
                $successCount = count($data);
            } catch (\Exception $e) {
                $errors[] = 'Database error: '.$e->getMessage();
            }
        }

        $message = '';
        if ($successCount > 0) {
            $message .= "{$successCount} medicines imported successfully.";
        }
        if (! empty($errors)) {
            $message .= ' '.count($errors).' errors occurred.';
        }

        return redirect()
            ->route('medicines.index')
            ->with($successCount > 0 ? 'success' : 'error', $message)
            ->with('import_errors', $errors);
    }

    public function show($id)
    {
        if (! auth()->user()->hasPermission('medicines')) {
            abort(403, 'Unauthorized');
        }

        $medicine = DB::table('medicines_lookup')->where('id', $id)->first();

        if (! $medicine) {
            abort(404, 'Resource not found');
        }

        // Get usage statistics
        $prescriptionCount = DB::table('prescriptions')->where('medicine_id', $id)->count();
        $lastPrescribed = DB::table('prescriptions')
            ->where('medicine_id', $id)
            ->orderByDesc('created_at')
            ->value('created_at');

        $medicine->prescription_count = $prescriptionCount;
        $medicine->last_prescribed = $lastPrescribed;

        return view('medicines.show', [
            'medicine' => $medicine,
        ]);
    }

    public function edit($id)
    {
        if (! auth()->user()->hasPermission('medicines')) {
            abort(403, 'Unauthorized');
        }

        $medicine = DB::table('medicines_lookup')->where('id', $id)->first();

        if (! $medicine) {
            abort(404, 'Resource not found');
        }

        return view('medicines.edit', [
            'medicine' => $medicine,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (! auth()->user()->hasPermission('medicines')) {
            abort(403, 'Unauthorized');
        }

        $medicine = DB::table('medicines_lookup')->where('id', $id)->first();

        if (! $medicine) {
            abort(404, 'Resource not found');
        }

        $validated = $request->validate([
            'medicine_name' => ['required', 'string', 'max:255', 'unique:medicines_lookup,medicine_name,'.$id],
            'category' => ['nullable', 'string', 'max:255'],
            'expiration_date' => ['nullable', 'date'],
        ]);

        DB::table('medicines_lookup')
            ->where('id', $id)
            ->update([
                'medicine_name' => $validated['medicine_name'],
                'category' => $validated['category'] ?? null,
                'expiration_date' => $validated['expiration_date'] ?? null,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('medicines.index')
            ->with('success', 'Medicine updated successfully.');
    }

    public function destroy($id)
    {
        // Check authorization
        if (! auth()->user()->hasPermission('medicines')) {
            abort(403, 'Unauthorized');
        }

        $medicine = DB::table('medicines_lookup')->where('id', $id)->first();

        if (! $medicine) {
            abort(404, 'Resource not found');
        }

        // Check if medicine is used in prescriptions
        $usedInPrescriptions = DB::table('prescriptions')->where('medicine_id', $id)->exists();

        if ($usedInPrescriptions) {
            return redirect()
                ->route('medicines.index')
                ->with('error', 'Cannot delete medicine that is used in prescriptions.');
        }

        DB::table('medicines_lookup')->where('id', $id)->delete();

        return redirect()
            ->route('medicines.index')
            ->with('success', 'Medicine deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        if (! auth()->user()->hasPermission('medicines')) {
            abort(403, 'Unauthorized');
        }

        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return redirect()
                ->route('medicines.index')
                ->with('error', 'No medicines selected.');
        }

        $deleted = 0;
        $failed = [];

        foreach ($ids as $id) {
            $medicine = DB::table('medicines_lookup')->where('id', $id)->first();

            if (! $medicine) {
                $failed[] = "Medicine ID {$id} not found.";
                continue;
            }

            $usedInPrescriptions = DB::table('prescriptions')->where('medicine_id', $id)->exists();

            if ($usedInPrescriptions) {
                $failed[] = "{$medicine->medicine_name} is used in prescriptions.";
                continue;
            }

            try {
                DB::table('medicines_lookup')->where('id', $id)->delete();
                $deleted++;
            } catch (\Exception $e) {
                $failed[] = "{$medicine->medicine_name}: DB error.";
            }
        }

        $message = '';
        if ($deleted > 0) {
            $message .= "{$deleted} medicine" . ($deleted > 1 ? 's' : '') . " deleted successfully.";
        }
        if (! empty($failed)) {
            $message .= ' '.count($failed).' could not be deleted.';
        }

        // Return errors (if any) in session under delete_errors
        return redirect()
            ->route('medicines.index')
            ->with($deleted > 0 ? 'success' : 'error', $message)
            ->with('delete_errors', $failed);
    }
}
