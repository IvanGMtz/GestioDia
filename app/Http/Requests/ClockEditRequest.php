<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ClockEditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'clocked_in_at' => ['required', 'date'],
            'clocked_out_at' => ['nullable', 'date', 'after:clocked_in_at'],
            'edit_reason' => ['required', 'string', 'max:255'],
        ];
    }
}
