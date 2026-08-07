<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFamilyPlanningClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('maternal');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type_of_client' => ['required', 'in:new_acceptor,continuing_user,drop_out,others'],
            'method' => ['required', 'string', 'in:Pills,Injectable,Implant,IUD,Condom,BTL,Calendar/Rhythm,LAM,Others'],
            'drop_out_reason' => ['nullable', 'required_if:type_of_client,drop_out', 'string', 'max:500'],
            'schedule_next_visit' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
