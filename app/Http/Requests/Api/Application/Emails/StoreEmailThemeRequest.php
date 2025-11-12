<?php

namespace DarkOak\Http\Requests\Api\Application\Emails;

use DarkOak\Models\AdminRole;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreEmailThemeRequest extends ApplicationApiRequest
{
    protected function colorRule(bool $required = true): string
    {
        $rule = 'string|regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/';

        if ($required) {
            $rule = 'required|' . $rule;
        }

        return $rule;
    }

    public function permission(): string
    {
        return AdminRole::EMAILS_UPDATE;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
            'primary_color' => $this->colorRule(),
            'secondary_color' => $this->colorRule(),
            'accent_color' => $this->colorRule(),
            'background_color' => $this->colorRule(),
            'body_color' => $this->colorRule(),
            'text_color' => $this->colorRule(),
            'muted_text_color' => $this->colorRule(),
            'button_color' => $this->colorRule(),
            'button_text_color' => $this->colorRule(),
            'logo_url' => 'nullable|url',
            'footer_text' => 'nullable|string',
            'set_default' => 'sometimes|boolean',
            'variant_mode' => 'sometimes|string|in:single,dual',
            'light_palette' => 'sometimes|array',
            'light_palette.primary' => 'required_if:variant_mode,dual|' . $this->colorRule(false),
            'light_palette.secondary' => 'required_if:variant_mode,dual|' . $this->colorRule(false),
            'light_palette.accent' => 'required_if:variant_mode,dual|' . $this->colorRule(false),
            'light_palette.background' => 'required_if:variant_mode,dual|' . $this->colorRule(false),
            'light_palette.body' => 'required_if:variant_mode,dual|' . $this->colorRule(false),
            'light_palette.text' => 'required_if:variant_mode,dual|' . $this->colorRule(false),
            'light_palette.muted' => 'required_if:variant_mode,dual|' . $this->colorRule(false),
            'light_palette.button' => 'required_if:variant_mode,dual|' . $this->colorRule(false),
            'light_palette.button_text' => 'required_if:variant_mode,dual|' . $this->colorRule(false),
        ];
    }
}

