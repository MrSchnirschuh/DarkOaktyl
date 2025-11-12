<?php

namespace Everest\Http\Controllers\Api\Application\Theme;

use Everest\Models\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Everest\Contracts\Repository\ThemeRepositoryInterface;
use Everest\Services\Themes\ThemePaletteService;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;
use Everest\Http\Requests\Application\Theme\UpdatePaletteRequest;

class ThemeController extends ApplicationApiController
{
    /**
     * ThemeController constructor.
     */
    public function __construct(
        private ThemeRepositoryInterface $settings,
        private ThemePaletteService $paletteService
    ) {
        parent::__construct();
    }

    public function palette(): JsonResponse
    {
        return response()->json($this->paletteService->getUnifiedPalettePayload());
    }

    public function updatePalette(UpdatePaletteRequest $request): JsonResponse
    {
        $payload = $request->validated();

        return response()->json(
            $this->paletteService->persistUnifiedPalette($payload['modes'] ?? [])
        );
    }

    /**
     * Update the colors for the panel theme.
     *
     * @throws \Throwable
     */
    public function colors(Request $request): Response
    {
        $this->settings->set('theme::colors:' . $request->input('key'), $request->input('value'));
        $this->paletteService->syncDefaultEmailTheme();

        return $this->returnNoContent();
    }

    /**
     * Reset all of the theme keys to factory defaults.
     */
    public function reset(): Response
    {
        foreach ($this->settings->all() as $setting) {
            $setting->delete();
        }

        $this->paletteService->syncDefaultEmailTheme();

        return $this->returnNoContent();
    }

    /**
     * Delete a single theme color key (used for removing presets cleanly).
     */
    public function deleteColor(Request $request): Response
    {
        $key = $request->input('key');
        if (! $key) {
            return $this->returnNoContent();
        }

        $this->settings->forget('theme::colors:' . $key);

        $this->paletteService->syncDefaultEmailTheme();

        return $this->returnNoContent();
    }
}
