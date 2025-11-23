<?php

namespace DarkOak\Models;

class LegalDocument extends Model
{
    protected $table = 'legal_documents';

    protected $fillable = [
        'slug',
        'title',
        'content',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'bool',
    ];
}
