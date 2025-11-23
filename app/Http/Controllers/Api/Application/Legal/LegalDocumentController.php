<?php

namespace DarkOak\Http\Controllers\Api\Application\Legal;

use DarkOak\Facades\Activity;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Legal\GetLegalDocumentsRequest;
use DarkOak\Http\Requests\Api\Application\Legal\UpdateLegalDocumentRequest;
use DarkOak\Models\LegalDocument;
use Illuminate\Http\Response;

class LegalDocumentController extends ApplicationApiController
{
    public function index(GetLegalDocumentsRequest $request): array
    {
        $this->ensureDefaultDocuments();

        return [
            'data' => LegalDocument::query()
                ->orderBy('slug')
                ->get()
                ->map(fn (LegalDocument $document) => $this->transform($document))
                ->all(),
        ];
    }

    public function show(GetLegalDocumentsRequest $request, string $slug): array
    {
        $this->ensureDefaultDocuments();
        $document = $this->getDocumentOrFail($slug);

        return ['data' => $this->transform($document)];
    }

    public function update(UpdateLegalDocumentRequest $request, string $slug): Response
    {
        $document = $this->getDocumentOrFail($slug);

        $document->fill([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'is_published' => $request->boolean('is_published'),
        ]);
        $document->save();

        Activity::event('admin:legal:document:update')
            ->property('slug', $document->slug)
            ->description('Legal document updated')
            ->log();

        return $this->returnNoContent();
    }

    private function transform(LegalDocument $document): array
    {
        return [
            'slug' => $document->slug,
            'title' => $document->title,
            'content' => $document->content,
            'is_published' => $document->is_published,
            'updated_at' => optional($document->updated_at)->toIso8601String(),
        ];
    }

    private function getDocumentOrFail(string $slug): LegalDocument
    {
        return LegalDocument::query()->where('slug', $slug)->firstOrFail();
    }

    private function ensureDefaultDocuments(): void
    {
        $legacy = config('legal.legacy_slugs', []);
        foreach ($legacy as $oldSlug => $newSlug) {
            $legacyDocument = LegalDocument::query()->where('slug', $oldSlug)->first();
            if (! $legacyDocument) {
                continue;
            }

            $conflictDocument = LegalDocument::query()->where('slug', $newSlug)->first();
            if ($conflictDocument) {
                continue;
            }

            $legacyDocument->slug = $newSlug;
            $legacyDocument->save();
        }

        $defaults = config('legal.defaults', []);

        foreach ($defaults as $document) {
            LegalDocument::query()->firstOrCreate(
                ['slug' => $document['slug']],
                [
                    'title' => $document['title'],
                    'content' => $document['content'] ?? '',
                    'is_published' => $document['is_published'] ?? true,
                ]
            );
        }
    }
}
