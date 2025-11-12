<?php

namespace DarkOak\Http\Requests\Application\Theme;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaletteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'modes' => ['required', 'array'],
            'modes.dark' => ['required', 'array'],
            'modes.light' => ['required', 'array'],
            'modes.dark.*' => ['nullable', 'string', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'modes.light.*' => ['nullable', 'string', 'regex:/^#([0-9a-fA-F]{6})$/'],
        ];
    }
}

