<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\IcdApiService;

class SearchController extends Controller
{
    /**
     * Search for Patients (by Name)
     */
    public function patients(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([]);
        }

        // Search by Last Name OR First Name
        // We limit to 10 results for speed
        $patients = DB::table('patients')
            ->where(function ($qb) use ($query) {
                $qb->where('last_name', 'LIKE', "{$query}%")
                    ->orWhere('first_name', 'LIKE', "{$query}%");
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->distinct()
            ->select('id', 'first_name', 'last_name', 'sex', 'date_of_birth')
            ->limit(10)
            ->get();

        // Format the results for the frontend
        $results = $patients->map(function ($patient) {
            $ptCode = 'PT'.str_pad((string) $patient->id, 3, '0', STR_PAD_LEFT);
            $age = null;
            if (! empty($patient->date_of_birth)) {
                $age = Carbon::parse($patient->date_of_birth)->age;
            }

            return [
                'id' => $patient->id,
                'text' => trim((string) $patient->last_name).', '.trim((string) $patient->first_name), // What shows in the dropdown
                'subtext' => $ptCode.' | '.trim((string) $patient->sex).($age !== null ? ' | '.$age.' y/o' : '').' | '.$patient->date_of_birth, // Extra info
            ];
        });

        return response()->json($results);
    }

    /**
     * Search for Diagnosis (ICD-10 or Name)
     */
    public function diagnoses(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([]);
        }

        // Try ICD API first (if enabled). If API is not configured or returns
        // no results, fall back to the local diagnosis_lookup table.
        $icdService = app()->make(IcdApiService::class);
        if ($icdService->isEnabled()) {
            $apiResults = $icdService->search($query, 15);
            if (! empty($apiResults)) {
                return response()->json($apiResults);
            }
        }

        $diagnoses = DB::table('diagnosis_lookup')
            ->where('diagnosis_name', 'LIKE', "%{$query}%")
            ->orWhere('diagnosis_code', 'LIKE', "{$query}%")
            ->select('id', 'diagnosis_code', 'diagnosis_name')
            ->limit(15)
            ->get();

        $results = $diagnoses->map(function ($d) {
            return [
                'id' => $d->id,
                'text' => $d->diagnosis_code ? ($d->diagnosis_code.' - '.$d->diagnosis_name) : $d->diagnosis_name,
            ];
        });

        return response()->json($results);
    }

    /**
     * Search for Medicines (Generic Name)
     */
    public function medicines(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([]);
        }

        $medicines = DB::table('medicines_lookup')
            ->where('medicine_name', 'LIKE', "%{$query}%")
            ->select('id', 'medicine_name')
            ->limit(15)
            ->get();

        $results = $medicines->map(function ($m) {
            return [
                'id' => $m->id,
                'text' => $m->medicine_name,
            ];
        });

        return response()->json($results);
    }

    /**
     * Search for Households (by Family Name Head / Zone / Contact)
     */
    public function households(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([]);
        }

        $households = DB::table('households')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->where(function ($qb) use ($query) {
                $qb->where('households.family_name_head', 'LIKE', "%{$query}%")
                    ->orWhere('zones.zone_number', 'LIKE', "%{$query}%")
                    ->orWhere('households.contact_number', 'LIKE', "%{$query}%");
            })
            ->distinct()
            ->select('households.id', 'households.family_name_head', 'zones.zone_number', 'households.contact_number')
            ->orderBy('zones.zone_number')
            ->orderBy('households.family_name_head')
            ->limit(15)
            ->get();

        $results = $households->map(function ($h) {
            $contact = $h->contact_number ? trim((string) $h->contact_number) : null;

            return [
                'id' => $h->id,
                'text' => (string) $h->family_name_head,
                'subtext' => 'Zone '.$h->zone_number.' | Household #'.$h->id.($contact ? ' | '.$contact : ''),
            ];
        });

        return response()->json($results);
    }
}
