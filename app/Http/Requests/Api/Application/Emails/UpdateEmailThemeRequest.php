<?php

namespace DarkOak\Http\Requests\Api\Application\Emails;

class UpdateEmailThemeRequest extends StoreEmailThemeRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $nullableKeys = [
            'name',
            'description',
            'primary_color',
            'secondary_color',
            'accent_color',
            'background_color',
            'body_color',
            'text_color',
            'muted_text_color',
            'button_color',
            'button_text_color',
            'logo_url',
            'footer_text',
        ];

        foreach ($nullableKeys as $key) {
            if (isset($rules[$key])) {
                $rules[$key] = preg_replace('/^required\|/', 'sometimes|', $rules[$key]);
            }
        }

        $rules['set_default'] = 'sometimes|boolean';

        return $rules;
    }
}

