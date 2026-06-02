<?php

namespace DarkOak\Models;

class LegalDocument extends Model
{
    public static array $validationRules = [
        'slug' => 'required|string|max:64|unique:legal_documents,slug',
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'is_published' => 'boolean',
    ];

    protected $table = 'legal_documents';

    protected $fillable = [
        'slug',
        'title',
        'content',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}