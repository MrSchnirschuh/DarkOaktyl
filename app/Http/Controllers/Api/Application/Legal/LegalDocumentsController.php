<?php

namespace DarkOak\Http\Controllers\Api\Application\Legal;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DarkOak\Models\LegalDocument;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LegalDocumentsController extends ApplicationApiController
{
    /**
     * LegalDocumentsController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return all legal documents.
     * If no documents exist in the DB, seed them with defaults.
     */
    public function index(): JsonResponse
    {
        $documents = LegalDocument::all();

        if ($documents->isEmpty()) {
            $documents = $this->seedDefaultDocuments();
        }

        $data = $documents->map(function (LegalDocument $doc) {
            return [
                'slug' => $doc->slug,
                'title' => $doc->title,
                'content' => $doc->content,
                'is_published' => (bool) $doc->is_published,
                'updated_at' => $doc->updated_at?->toISOString(),
            ];
        });

        return new JsonResponse(['data' => $data]);
    }

    /**
     * Update a specific legal document by slug.
     */
    public function update(string $slug, Request $request): JsonResponse
    {
        $document = LegalDocument::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'is_published' => 'sometimes|boolean',
        ]);

        if (isset($validated['title'])) {
            $document->title = $validated['title'];
        }

        if (isset($validated['content'])) {
            $document->content = $validated['content'];
        }

        if (isset($validated['is_published'])) {
            $document->is_published = (bool) $validated['is_published'];
        }

        $document->skipValidation()->save();

        return new JsonResponse(['data' => [
            'slug' => $document->slug,
            'title' => $document->title,
            'content' => $document->content,
            'is_published' => (bool) $document->is_published,
            'updated_at' => $document->updated_at?->toISOString(),
        ]]);
    }

    /**
     * Public endpoint: return a specific published document by slug.
     */
    public function showPublished(string $slug): JsonResponse
    {
        $document = LegalDocument::where('slug', $slug)->where('is_published', true)->first();

        if (!$document) {
            $seeded = $this->seedDefaultDocuments();
            foreach ($seeded as $doc) {
                if ($doc->slug === $slug) {
                    $document = $doc;
                    break;
                }
            }
        }

        if (!$document) {
            throw new NotFoundHttpException('Legal document not found.');
        }

        return new JsonResponse(['data' => [
            'slug' => $document->slug,
            'title' => $document->title,
            'content' => $document->content,
            'updated_at' => $document->updated_at?->toISOString(),
        ]]);
    }

    /**
     * Public endpoint: return published documents for the legal pages.
     */
    public function published(): JsonResponse
    {
        $documents = LegalDocument::where('is_published', true)->get();

        if ($documents->isEmpty()) {
            $documents = $this->seedDefaultDocuments();
        }

        $data = $documents->map(function (LegalDocument $doc) {
            return [
                'slug' => $doc->slug,
                'title' => $doc->title,
                'content' => $doc->content,
                'updated_at' => $doc->updated_at?->toISOString(),
            ];
        });

        return new JsonResponse(['data' => $data]);
    }

    /**
     * Seed default legal documents if none exist.
     */
    private function seedDefaultDocuments(): array
    {
        $defaults = [
            [
                'slug' => 'terms-of-service',
                'title' => 'Terms of Service',
                'content' => implode("\n", [
                    'Terms of Service',
                    '',
                    'Last updated: ' . date('F j, Y'),
                    '',
                    '1. Acceptance of Terms',
                    '',
                    'By accessing and using this service, you accept and agree to be bound by the terms and provisions of this agreement. If you do not agree to abide by the above, please do not use this service.',
                    '',
                    '2. Service Description',
                    '',
                    'This service provides game server hosting and related infrastructure services. We reserve the right to modify, suspend, or discontinue any aspect of the service at any time.',
                    '',
                    '3. User Responsibilities',
                    '',
                    'You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account. You agree to notify us immediately of any unauthorized use of your account.',
                    '',
                    '4. Prohibited Activities',
                    '',
                    'You may not use the service for any unlawful purpose or in violation of any applicable laws. This includes, but is not limited to, hosting illegal content, distributing malware, or conducting attacks against other systems.',
                    '',
                    '5. Limitation of Liability',
                    '',
                    'This service is provided "as is" without any warranty of any kind. We shall not be liable for any damages arising from the use or inability to use the service.',
                    '',
                    '6. Termination',
                    '',
                    'We reserve the right to terminate or suspend your access to the service at any time, without prior notice, for conduct that we believe violates these terms or is harmful to other users or the service.',
                    '',
                    '7. Changes to Terms',
                    '',
                    'We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting. Your continued use of the service after any changes indicates your acceptance of the new terms.',
                    '',
                    '8. Contact',
                    '',
                    'If you have any questions about these terms, please contact us through the administration panel.',
                ]),
                'is_published' => true,
            ],
            [
                'slug' => 'legal-notice',
                'title' => 'Legal Notice',
                'content' => implode("\n", [
                    'Legal Notice / Imprint',
                    '',
                    'Last updated: ' . date('F j, Y'),
                    '',
                    'Provider Information',
                    '',
                    'This service is operated by:',
                    '',
                    'DarkOak.eu',
                    '',
                    'Contact:',
                    'Email: support@darkoak.eu',
                    '',
                    'Disclaimer',
                    '',
                    '1. Content Liability',
                    '',
                    'The content of this service has been created with the utmost care. However, we cannot guarantee the accuracy, completeness, or timeliness of the content.',
                    '',
                    '2. External Links',
                    '',
                    'This service may contain links to external third-party websites. We have no control over the content of those sites and assume no liability for them.',
                    '',
                    '3. Data Protection',
                    '',
                    'We take data protection seriously. Personal data is collected, processed, and used in accordance with applicable data protection laws. For more information, please contact us.',
                    '',
                    '4. Copyright',
                    '',
                    'All content, design, and code of this service are protected by copyright. Reproduction, modification, or redistribution without prior written consent is prohibited.',
                    '',
                    '5. Server Location',
                    '',
                    'The servers hosting this service may be located in various jurisdictions. By using this service, you consent to the transfer of your data to these locations.',
                ]),
                'is_published' => true,
            ],
        ];

        $created = [];
        foreach ($defaults as $data) {
            $doc = new LegalDocument();
            $doc->slug = $data['slug'];
            $doc->title = $data['title'];
            $doc->content = $data['content'];
            $doc->is_published = $data['is_published'];
            $doc->skipValidation()->save();
            $created[] = $doc;
        }

        return $created;
    }
}