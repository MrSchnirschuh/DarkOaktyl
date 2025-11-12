<?php

namespace Everest\Http\ViewComposers;

use Illuminate\View\View;
use Everest\Services\Themes\ThemePaletteService;

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
        $canonical = $this->paletteService->getCanonicalPalettes();

        $view->with('themeConfiguration', [
            'colors' => $this->paletteService->getRawColors(),
            'palettes' => array_map(static function (array $palette): array {
                return $palette['tokens'];
            }, $canonical),
            'textPalettes' => array_map(static function (array $palette): array {
                return $palette['text'];
            }, $canonical),
            'surfacePalettes' => array_map(static function (array $palette): array {
                return $palette['surfaces'];
            }, $canonical),
            'emailPalettes' => array_map(static function (array $palette): array {
                return $palette['email'];
            }, $canonical),
        ]);
    }
}
