<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\MedicineImportService;
use App\Services\MedicineService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MedicineController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission('medicines');

        $medicines = DB::table('medicines_lookup')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('medicines.index', [
            'medicines' => $medicines,
        ]);
    }

    public function create(): View
    {
        $this->authorizePermission('medicines');

        return view('medicines.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('medicines');

        $request->merge(['name' => $request->input('name', $request->input('medicine_name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:medicines_lookup,name'],
            'form' => ['nullable', 'string', 'max:255'],
        ]);

        MedicineService::create($validated);

        return redirect()
            ->route('medicines.index')
            ->with('success', 'Medicine added successfully.');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorizePermission('medicines');

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $result = MedicineImportService::import($request->file('csv_file')->getRealPath());

        if ($result['fatal_error'] !== null) {
            AuditLog::query()->create([
                'user_id' => Auth::id(),
                'action' => 'medicines_import_failed',
                'table_name' => 'medicines_lookup',
                'record_id' => null,
                'old_values' => null,
                'new_values' => ['fatal_error' => $result['fatal_error']],
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            return redirect()
                ->route('medicines.index')
                ->with('error', $result['fatal_error']);
        }

        $successCount = $result['success_count'];
        $errors = $result['errors'];

        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'medicines_imported',
            'table_name' => 'medicines_lookup',
            'record_id' => null,
            'old_values' => null,
            'new_values' => [
                'success_count' => $successCount,
                'error_count' => count($errors),
            ],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

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

    public function show($id): View
    {
        $this->authorizePermission('medicines');

        $medicine = MedicineService::findOrFail($id);

        $usage = MedicineService::usage($id);
        $medicine->prescription_count = $usage['prescription_count'];
        $medicine->last_prescribed = $usage['last_prescribed'];

        return view('medicines.show', [
            'medicine' => $medicine,
        ]);
    }

    public function edit($id): View
    {
        $this->authorizePermission('medicines');

        return view('medicines.edit', [
            'medicine' => MedicineService::findOrFail($id),
        ]);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $this->authorizePermission('medicines');

        MedicineService::findOrFail($id);

        $request->merge(['name' => $request->input('name', $request->input('medicine_name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:medicines_lookup,name,'.$id],
            'form' => ['nullable', 'string', 'max:255'],
        ]);

        MedicineService::update($id, $validated);

        return redirect()
            ->route('medicines.index')
            ->with('success', 'Medicine updated successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $this->authorizePermission('medicines');

        MedicineService::findOrFail($id);

        if (MedicineService::isUsedInPrescriptions($id)) {
            return redirect()
                ->route('medicines.index')
                ->with('error', 'Cannot delete medicine that is used in prescriptions.');
        }

        MedicineService::destroy($id);

        return redirect()
            ->route('medicines.index')
            ->with('success', 'Medicine deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $this->authorizePermission('medicines');

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

            if (MedicineService::isUsedInPrescriptions($id)) {
                $failed[] = "{$medicine->name} is used in prescriptions.";

                continue;
            }

            try {
                MedicineService::destroy($id);
                $deleted++;
            } catch (\Exception $e) {
                $failed[] = "{$medicine->name}: DB error.";
            }
        }

        $message = '';
        if ($deleted > 0) {
            $message .= "{$deleted} medicine".($deleted > 1 ? 's' : '').' deleted successfully.';
        }
        if (! empty($failed)) {
            $message .= ' '.count($failed).' could not be deleted.';
        }

        return redirect()
            ->route('medicines.index')
            ->with($deleted > 0 ? 'success' : 'error', $message)
            ->with('delete_errors', $failed);
    }
}
