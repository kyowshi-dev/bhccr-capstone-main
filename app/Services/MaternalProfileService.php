<?php

namespace App\Services;

use App\Models\MaternalProfile;
use App\Models\Patient;

class MaternalProfileService
{
    public function upsert(Patient $patient, array $data): MaternalProfile
    {
        return MaternalProfile::updateOrCreate(
            ['patient_id' => $patient->id],
            $data
        );
    }
}
