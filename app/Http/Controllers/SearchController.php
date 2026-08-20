<?php

namespace App\Http\Controllers;

use App\Helpers\PatientCode;
use App\Services\FuzzySearchService;
use App\Services\IcdApiService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Zone scoping helper for patient queries.
     *
     * BHWs are allowed to search all patients, not just those
     * in their assigned puroks/zones.
     */
    private function scopePatientQuery(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Search for Patients (by Name)
     *
     * Fast indexed prefix match first; when that cannot fill the result
     * list, a fuzzy (Levenshtein) re-rank over a bounded candidate pool
     * (first-letter + substring matches) tolerates spelling errors.
     */
    public function patients(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([]);
        }

        $limit = 10;

        $prefixResults = $this->scopePatientQuery(DB::table('patients'))
            ->where(function ($qb) use ($query) {
                $qb->where('last_name', 'LIKE', "{$query}%")
                    ->orWhere('first_name', 'LIKE', "{$query}%");
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->distinct()
            ->select('id', 'first_name', 'last_name', 'sex', 'date_of_birth')
            ->limit($limit)
            ->get();

        if ($prefixResults->count() < $limit) {
            $prefixResults = $prefixResults->merge(
                $this->fuzzyPatientCandidates($query, $prefixResults, $limit)
            );
        }

        return response()->json($this->formatPatients($prefixResults->take($limit)));
    }

    /**
     * Build a bounded candidate pool (first-letter and substring matches)
     * and re-rank it by edit distance against the query. Prefix matches
     * already returned are excluded.
     *
     * @param  Collection<int, \stdClass>  $alreadyReturned
     * @return Collection<int, \stdClass>
     */
    private function fuzzyPatientCandidates(string $query, $alreadyReturned, int $limit)
    {
        $firstChar = mb_substr($query, 0, 1);

        if ($firstChar === '') {
            return collect();
        }

        $candidates = $this->scopePatientQuery(DB::table('patients'))
            ->where(function ($qb) use ($query, $firstChar) {
                $qb->where('last_name', 'LIKE', "{$firstChar}%")
                    ->orWhere('first_name', 'LIKE', "{$firstChar}%")
                    ->orWhere('last_name', 'LIKE', "%{$query}%")
                    ->orWhere('first_name', 'LIKE', "%{$query}%");
            })
            ->whereNotIn('id', $alreadyReturned->pluck('id'))
            ->distinct()
            ->select('id', 'first_name', 'last_name', 'sex', 'date_of_birth')
            ->limit(500)
            ->get();

        $fuzzy = app(FuzzySearchService::class)->rank(
            $candidates,
            $query,
            fn ($p): string => trim((string) $p->last_name).' '.trim((string) $p->first_name),
            $limit
        );

        return collect($fuzzy);
    }

    /**
     * Format patient rows for the autocomplete dropdown.
     *
     * @param  Collection<int, \stdClass>  $patients
     * @return array<int, array<string, mixed>>
     */
    private function formatPatients($patients): array
    {
        $patientIds = $patients->pluck('id')->toArray();

        $activePregnancyIds = DB::table('pregnancies')
            ->whereIn('patient_id', $patientIds)
            ->where('status', 'active')
            ->pluck('patient_id')
            ->toArray();

        return $patients->map(function ($patient) use ($activePregnancyIds) {
            $ptCode = PatientCode::format((int) $patient->id);
            $age = null;
            if (! empty($patient->date_of_birth)) {
                $age = Carbon::parse($patient->date_of_birth)->age;
            }

            return [
                'id' => $patient->id,
                'text' => trim((string) $patient->last_name).', '.trim((string) $patient->first_name), // What shows in the dropdown
                'subtext' => $ptCode.' | '.trim((string) $patient->sex).($age !== null ? ' | '.$age.' y/o' : '').' | '.$patient->date_of_birth, // Extra info
                'has_active_pregnancy' => in_array($patient->id, $activePregnancyIds, true),
            ];
        })->all();
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

        // ICD API is the primary source when enabled: API results are persisted
        // into diagnosis_lookup so the autocomplete returns local row ids that
        // AddDiagnosisRequest and the diagnosis_records foreign key accept.
        // Falls back to the local table when the API is disabled, unreachable,
        // or returns no results.
        $icdService = app()->make(IcdApiService::class);
        if ($icdService->isEnabled()) {
            $apiResults = $icdService->search($query, 15);
            if (! empty($apiResults)) {
                return response()->json($this->mergeApiResults($query, $apiResults));
            }
        }

        return response()->json($this->localDiagnoses($query));
    }

    /**
     * Persist ICD API results into diagnosis_lookup (non-destructive upsert
     * keyed on diagnosis_code) and return them with local row ids, appending
     * local matches whose codes the API did not return.
     *
     * @param  array<int, array<string, mixed>>  $apiResults
     * @return array<int, array{id: int, text: string}>
     */
    private function mergeApiResults(string $query, array $apiResults): array
    {
        $codes = [];
        $upsertRows = [];

        foreach ($apiResults as $result) {
            $code = $result['id'] ?? null;
            $text = $result['text'] ?? '';

            if (! is_string($code) || $code === '' || ! str_starts_with($text, $code.' - ')) {
                continue;
            }

            $codes[] = $code;
            $upsertRows[] = [
                'diagnosis_code' => $code,
                'diagnosis_name' => trim(substr($text, strlen($code) + 3)),
            ];
        }

        if ($codes === []) {
            return $this->localDiagnoses($query);
        }

        DB::table('diagnosis_lookup')->upsert($upsertRows, ['diagnosis_code'], ['diagnosis_name']);

        $localByCode = DB::table('diagnosis_lookup')
            ->whereIn('diagnosis_code', $codes)
            ->select('id', 'diagnosis_code', 'diagnosis_name')
            ->get()
            ->keyBy('diagnosis_code');

        $results = [];
        foreach ($codes as $code) {
            $row = $localByCode->get($code);
            if ($row === null) {
                continue;
            }

            $results[] = [
                'id' => (int) $row->id,
                'text' => $row->diagnosis_code.' - '.$row->diagnosis_name,
            ];
        }

        $localMatches = DB::table('diagnosis_lookup')
            ->where(function ($qb) use ($query) {
                $qb->where('diagnosis_name', 'LIKE', "%{$query}%")
                    ->orWhere('diagnosis_code', 'LIKE', "{$query}%");
            })
            ->whereNotIn('diagnosis_code', $codes)
            ->select('id', 'diagnosis_code', 'diagnosis_name')
            ->limit(15)
            ->get();

        foreach ($localMatches as $d) {
            $results[] = [
                'id' => (int) $d->id,
                'text' => $d->diagnosis_code ? ($d->diagnosis_code.' - '.$d->diagnosis_name) : $d->diagnosis_name,
            ];
        }

        return array_slice($results, 0, 15);
    }

    /**
     * Local diagnosis fallback. Codes are matched strictly (prefix/
     * substring) - never fuzzed, since a wrong code suggestion is a
     * clinical safety issue. Names get a fuzzy re-rank when the strict
     * pass cannot fill the result list.
     *
     * @return array<int, array{id: int, text: string}>
     */
    private function localDiagnoses(string $query): array
    {
        $limit = 15;

        $diagnoses = DB::table('diagnosis_lookup')
            ->where('diagnosis_name', 'LIKE', "%{$query}%")
            ->orWhere('diagnosis_code', 'LIKE', "{$query}%")
            ->select('id', 'diagnosis_code', 'diagnosis_name')
            ->limit($limit)
            ->get();

        if ($diagnoses->count() < $limit) {
            $firstChar = mb_substr($query, 0, 1);

            if ($firstChar !== '') {
                $candidates = DB::table('diagnosis_lookup')
                    ->where('diagnosis_name', 'LIKE', "{$firstChar}%")
                    ->whereNotIn('id', $diagnoses->pluck('id'))
                    ->select('id', 'diagnosis_code', 'diagnosis_name')
                    ->limit(300)
                    ->get();

                $fuzzy = app(FuzzySearchService::class)->rank(
                    $candidates,
                    $query,
                    fn ($d): string => (string) $d->diagnosis_name,
                    $limit - $diagnoses->count()
                );

                $diagnoses = $diagnoses->concat($fuzzy);
            }
        }

        return $diagnoses->map(function ($d) {
            return [
                'id' => (int) $d->id,
                'text' => $d->diagnosis_code ? ($d->diagnosis_code.' - '.$d->diagnosis_name) : $d->diagnosis_name,
            ];
        })->all();
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
            ->whereNull('deleted_at')
            ->where('name', 'LIKE', "%{$query}%")
            ->select('id', 'name', 'form')
            ->limit(15)
            ->get();

        $results = $medicines->map(function ($m) {
            return [
                'id' => $m->id,
                'text' => $m->name,
                'form' => $m->form,
            ];
        });

        return response()->json($results);
    }

    /**
     * Search for Households (by Family Name Head / Zone / Contact)
     *
     * Strict substring pass first; fuzzy re-rank on the family name head
     * fills the list when the strict pass comes up short.
     */
    public function households(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([]);
        }

        $limit = 15;

        $households = DB::table('households')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->when(
                auth()->user()?->isZoneScoped(),
                fn ($query) => $query->whereIn('households.zone_id', auth()->user()->accessibleZoneIds())
            )
            ->where(function ($qb) use ($query) {
                $qb->where('households.family_name_head', 'LIKE', "%{$query}%")
                    ->orWhere('zones.zone_number', 'LIKE', "%{$query}%")
                    ->orWhere('households.contact_number', 'LIKE', "%{$query}%");
            })
            ->distinct()
            ->select('households.id', 'households.family_name_head', 'zones.zone_number', 'households.contact_number')
            ->orderBy('zones.zone_number')
            ->orderBy('households.family_name_head')
            ->limit($limit)
            ->get();

        if ($households->count() < $limit) {
            $firstChar = mb_substr($query, 0, 1);

            if ($firstChar !== '') {
                $candidates = DB::table('households')
                    ->join('zones', 'households.zone_id', '=', 'zones.id')
                    ->when(
                        auth()->user()?->isZoneScoped(),
                        fn ($q) => $q->whereIn('households.zone_id', auth()->user()->accessibleZoneIds())
                    )
                    ->where('households.family_name_head', 'LIKE', "{$firstChar}%")
                    ->whereNotIn('households.id', $households->pluck('id'))
                    ->distinct()
                    ->select('households.id', 'households.family_name_head', 'zones.zone_number', 'households.contact_number')
                    ->limit(300)
                    ->get();

                $fuzzy = app(FuzzySearchService::class)->rank(
                    $candidates,
                    $query,
                    fn ($h): string => (string) $h->family_name_head,
                    $limit - $households->count()
                );

                $households = $households->concat($fuzzy);
            }
        }

        $results = $households->take($limit)->map(function ($h) {
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
