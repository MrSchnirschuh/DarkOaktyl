<?php

namespace DarkOak\Http\Controllers\Base;

use DarkOak\Http\Controllers\Controller;
use DarkOak\Models\LegalDocument;
use Illuminate\View\View;

class LegalPageController extends Controller
{
    public function termsOfService(): View
    {
        return $this->renderDocument('terms-of-service');
    }

    public function legalNotice(): View
    {
        return $this->renderDocument('legal-notice');
    }

    protected function renderDocument(string $slug): View
    {
        $document = LegalDocument::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if (! $document) {
            $legacySlug = $this->resolveLegacySlug($slug);

            if ($legacySlug) {
                $document = LegalDocument::query()
                    ->where('slug', $legacySlug)
                    ->where('is_published', true)
                    ->first();
            }
        }

        abort_if(! $document, 404);

        return view('legal.show', [
            'document' => $document,
        ]);
    }

    private function resolveLegacySlug(string $current): ?string
    {
        $legacy = config('legal.legacy_slugs', []);

        foreach ($legacy as $old => $target) {
            if ($target === $current) {
                return $old;
            }
        }

        return null;
    }
}
