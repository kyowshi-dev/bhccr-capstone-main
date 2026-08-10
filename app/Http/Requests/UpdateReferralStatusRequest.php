<?php

namespace App\Http\Requests;

use App\Models\OutwardReferral;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReferralStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(OutwardReferral::STATUSES)],
        ];
    }
}
