<?php

namespace DarkOak\Http\ViewComposers;

use Illuminate\View\View;
use DarkOak\Services\Themes\ThemePaletteService;

class ThemeComposer
{
    public function __construct(private ThemePaletteService $paletteService)
    {
    }

    /**
     * Provide access to the asset service in the views.
     */
    public function compose(View $view): void
    {
        $view->with('themeConfiguration', $this->paletteService->getThemeConfiguration());
    }
}

