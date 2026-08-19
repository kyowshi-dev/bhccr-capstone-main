<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportMedicineRequest;
use App\Http\Requests\StoreMedicineRequest;
use App\Http\Requests\UpdateMedicineRequest;
use App\Models\AuditLog;
use App\Models\Medicine;
use App\Services\MedicineImportService;
use App\Services\MedicineService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicineController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePermission('medicines');

        $status = $request->input('status', 'active');

        if (! in_array($status, ['active', 'all', 'archived'], true)) {
            $status = 'active';
        }

        $query = match ($status) {
            'all' => Medicine::withTrashed(),
            'archived' => Medicine::onlyTrashed(),
            default => Medicine::query(),
        };

        $medicines = $query
            ->orderBy('name')
            ->paginate(pageSize(25))
            ->withQueryString();

        $activeCount = Medicine::query()->count();
        $archivedCount = Medicine::onlyTrashed()->count();

        return view('medicines.index', [
            'medicines' => $medicines,
            'status' => $status,
            'activeCount' => $activeCount,
            'archivedCount' => $archivedCount,
            'totalCount' => $activeCount + $archivedCount,
        ]);
    }

    public function create(): View
    {
        $this->authorizePermission('medicines');

        return view('medicines.create');
    }

    public function store(StoreMedicineRequest $request): RedirectResponse
    {
        $this->authorizePermission('medicines');

        MedicineService::create($request->validated());

        return redirect()
            ->route('medicines.index')
            ->with('success', 'Medicine added successfully.');
    }

    public function import(ImportMedicineRequest $request): RedirectResponse
    {
        $this->authorizePermission('medicines');

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

    public function update(UpdateMedicineRequest $request, $id): RedirectResponse
    {
        $this->authorizePermission('medicines');

        MedicineService::findOrFail($id);

        MedicineService::update($id, $request->validated());

        return redirect()
            ->route('medicines.index')
            ->with('success', 'Medicine updated successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $this->authorizePermission('medicines');

        MedicineService::destroy($id);

        return redirect()
            ->route('medicines.index')
            ->with('success', 'Medicine archived.');
    }

    public function restore($id): RedirectResponse
    {
        $this->authorizePermission('medicines');

        MedicineService::restore($id);

        return redirect()
            ->route('medicines.index')
            ->with('success', 'Medicine restored successfully.');
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

        foreach ($ids as $id) {
            $medicine = Medicine::query()->find($id);

            if (! $medicine) {
                continue;
            }

            $medicine->delete();
            $deleted++;
        }

        $message = "{$deleted} medicine".($deleted > 1 ? 's' : '').' archived.';

        return redirect()
            ->route('medicines.index')
            ->with('success', $message);
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $this->authorizePermission('medicines');

        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return redirect()
                ->route('medicines.index')
                ->with('error', 'No medicines selected.');
        }

        $restored = 0;

        foreach ($ids as $id) {
            $medicine = Medicine::onlyTrashed()->find($id);

            if (! $medicine) {
                continue;
            }

            $medicine->restore();
            $restored++;
        }

        $message = "{$restored} medicine".($restored > 1 ? 's' : '').' restored.';

        return redirect()
            ->route('medicines.index')
            ->with('success', $message);
    }
}
