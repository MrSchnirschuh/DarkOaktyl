<?php

namespace Everest\Http\Controllers\Api\Application\Theme;

use Everest\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Everest\Contracts\Repository\ThemeRepositoryInterface;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;

class ThemeController extends ApplicationApiController
{
    /**
     * ThemeController constructor.
     */
    public function __construct(
        private ThemeRepositoryInterface $settings
    ) {
        parent::__construct();
    }

    /**
     * Update the colors for the panel theme.
     *
     * @throws \Throwable
     */
    public function colors(Request $request): Response
    {
        $this->settings->set('theme::colors:' . $request->input('key'), $request->input('value'));

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

        return $this->returnNoContent();
    }
}
