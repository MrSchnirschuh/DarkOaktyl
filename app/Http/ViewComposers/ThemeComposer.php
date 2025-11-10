<?php

namespace Everest\Http\ViewComposers;

use Illuminate\View\View;
use Everest\Contracts\Repository\ThemeRepositoryInterface;

class ThemeComposer
{
    public function __construct(private ThemeRepositoryInterface $settings)
    {
    }

    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view): void
    {
        // Load defaults from config and override with any stored repository values.
        $defaults = config('modules.theme.colors', []);

        $colors = [];
        // Start with config defaults
        foreach ($defaults as $k => $v) {
            $colors[$k] = $v;
        }

        // Override with stored values from the repository (if present).
        try {
            foreach ($this->settings->all() as $setting) {
                if (str_starts_with($setting->key, 'theme::colors:')) {
                    $name = substr($setting->key, strlen('theme::colors:'));
                    $colors[$name] = $setting->value;
                }
            }
        } catch (\Throwable $e) {
            // If repository->all() fails for some reason, continue with defaults.
        }

        $view->with('themeConfiguration', [
            'colors' => $colors,
        ]);
    }
}
