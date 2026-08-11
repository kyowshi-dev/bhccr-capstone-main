<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddWatchlistEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('maternal.manage_watchlist');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'program_type' => ['required', 'string', 'in:prenatal,postnatal,fp,general'],
            'reason_code' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
