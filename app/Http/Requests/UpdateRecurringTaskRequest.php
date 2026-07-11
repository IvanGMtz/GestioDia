<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecurringTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'requires_photo' => $this->boolean('requires_photo'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'assigned_member_id' => [
                'nullable',
                'integer',
                Rule::exists('members', 'id')->where(fn ($query) => $query->where('team_id', app(Team::class)->id)),
            ],
            'requires_photo' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
