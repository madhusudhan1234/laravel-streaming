<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Episode;
use PHPUnit\Framework\TestCase;

final class EpisodeModelTest extends TestCase
{
    public function test_audio_url_defaults_to_public_audios_path(): void
    {
        $episode = new Episode;
        $episode->filename = 'first-episode.m4a';
        $episode->url = null;

        $this->assertSame('/audios/first-episode.m4a', $episode->audio_url);
    }

    public function test_audio_url_uses_explicit_url_when_available(): void
    {
        $episode = new Episode;
        $episode->filename = 'first-episode.m4a';
        $episode->url = 'http://cdn.example.com/podcast/first-episode.m4a';

        $this->assertSame('http://cdn.example.com/podcast/first-episode.m4a', $episode->audio_url);
    }

    public function test_is_stored_on_r2_returns_true_for_http_url(): void
    {
        $episode = new Episode;
        $episode->url = 'https://cdn.example.com/episodes/123.m4a';

        $this->assertTrue($episode->isStoredOnR2());
    }

    public function test_is_stored_on_r2_returns_false_for_local_path(): void
    {
        $episode = new Episode;
        $episode->url = '/audios/first-episode.m4a';

        $this->assertFalse($episode->isStoredOnR2());
    }

    public function test_is_stored_on_r2_returns_false_when_url_is_null(): void
    {
        $episode = new Episode;
        $episode->url = null;

        $this->assertFalse($episode->isStoredOnR2());
    }
}
