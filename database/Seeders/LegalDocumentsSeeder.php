<?php

namespace Database\Seeders;

use DarkOak\Models\LegalDocument;
use Illuminate\Database\Seeder;

class LegalDocumentsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = config('legal.defaults', []);

        foreach ($defaults as $document) {
            LegalDocument::query()->updateOrCreate(
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
