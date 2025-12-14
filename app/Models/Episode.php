<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    protected $fillable = [
        'id',
        'title',
        'filename',
        'url',
        'storage_disk',
        'duration',
        'file_size',
        'format',
        'published_date',
        'description',
    ];

    protected $casts = [
        'published_date' => 'date',
    ];

    public function getAudioUrlAttribute(): string
    {
        return $this->url ?? "/audios/{$this->filename}";
    }

    public function isStoredOnR2(): bool
    {
        return str_starts_with($this->url ?? '', 'http');
    }
}
