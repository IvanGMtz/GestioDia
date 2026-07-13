<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteTaskRequest extends FormRequest
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
        $requiresPhoto = (bool) $this->route('task')?->requires_photo;

        return [
            'note' => ['nullable', 'string', 'max:500'],
            'photo' => [
                $requiresPhoto ? 'required' : 'nullable',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                // Sin compresión previa en el navegador (AGENT.md §7 revisado): se sube
                // la foto tal cual la entrega la cámara, así que el límite cubre el
                // tamaño de archivo original (no el post-compresión del spec inicial).
                'max:30720',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Esta tarea requiere una foto de evidencia.',
            'photo.mimes' => 'Formato de foto no soportado. Si tu iPhone guarda las fotos en HEIC, cambia a "Más compatible" en Ajustes → Cámara → Formatos, y vuelve a intentarlo.',
            'photo.max' => 'La foto pesa demasiado (máx. 30 MB).',
        ];
    }
}
