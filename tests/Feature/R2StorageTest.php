<?php

namespace Tests\Feature;

use App\Models\Episode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class R2StorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock R2 storage for testing
        Storage::fake('r2');
        Storage::fake('local');
    }

    public function test_episode_upload_to_r2_when_configured()
    {
        // Create and authenticate a user
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        // Set R2 as default storage and configure R2 with all required settings
        config(['filesystems.default' => 'r2']);
        config(['filesystems.disks.r2.key' => 'test-key']);
        config(['filesystems.disks.r2.secret' => 'test-secret']);
        config(['filesystems.disks.r2.bucket' => 'test-bucket']);
        config(['filesystems.disks.r2.endpoint' => 'https://test.r2.cloudflarestorage.com']);

        $file = UploadedFile::fake()->create('test-episode.mp3', 1000, 'audio/mpeg');

        $response = $this->post('/dashboard/episodes', [
            'title' => 'Test R2 Episode',
            'description' => 'Test description',
            'published_date' => '2024-03-23',
            'audio_file' => $file,
        ]);

        $response->assertRedirect(route('episodes.dashboard'));

        // Check episode was created
        $episode = Episode::where('title', 'Test R2 Episode')->first();
        $this->assertNotNull($episode);

        // Check that the URL indicates R2 storage (should contain the R2 domain or be a full URL)
        $this->assertNotNull($episode->url);
        $this->assertStringContainsString('test-episode.mp3', $episode->url);

        // For R2 uploads, the filename should be stored in episodes/ directory
        // The filename will have a timestamp prefix, so we need to find the actual stored file
        $files = Storage::disk('r2')->files('episodes');
        $testFile = collect($files)->first(function ($file) {
            return str_contains($file, 'test-episode.mp3');
        });

        $this->assertNotNull($testFile, 'Expected file not found in R2 storage');
        $this->assertTrue(Storage::disk('r2')->exists($testFile));
    }

    public function test_episode_upload_to_local_when_r2_not_configured()
    {
        // Create and authenticate a user
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        // Set local as default storage and clear R2 config
        config(['filesystems.default' => 'local']);
        config(['filesystems.disks.r2.key' => null]);
        config(['filesystems.disks.r2.bucket' => null]);

        // Mock file upload
        $file = UploadedFile::fake()->create('test-episode.mp3', 1000);

        // Make request to create episode
        $response = $this->post('/dashboard/episodes', [
            'title' => 'Test Local Episode',
            'description' => 'Test description',
            'audio_file' => $file,
            'published_date' => '2024-03-20',
        ]);

        $response->assertRedirect(route('episodes.dashboard'));

        // Check episode was created
        $episode = Episode::where('title', 'Test Local Episode')->first();
        $this->assertNotNull($episode);

        // For local storage, URL should start with /audios/
        $this->assertStringStartsWith('/audios/', $episode->url);

        // Check file was uploaded to local storage (public/audios directory)
        $this->assertTrue(file_exists(public_path($episode->url)));
    }

    public function test_episode_update_with_new_file_uploads_to_r2()
    {
        // Create and authenticate a user
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        // Create existing episode
        $episode = Episode::create([
            'title' => 'Original Episode',
            'filename' => 'original.mp3',
            'url' => '/audios/original.mp3',
            'duration' => '03:00',
            'file_size' => '4.0 MB',
            'format' => 'MP3',
            'published_date' => '2024-03-20',
            'description' => 'Original description',
        ]);

        // Set R2 as default storage and configure R2 with all required settings
        config(['filesystems.default' => 'r2']);
        config(['filesystems.disks.r2.key' => 'test-key']);
        config(['filesystems.disks.r2.secret' => 'test-secret']);
        config(['filesystems.disks.r2.bucket' => 'test-bucket']);
        config(['filesystems.disks.r2.endpoint' => 'https://test.r2.cloudflarestorage.com']);

        $newFile = UploadedFile::fake()->create('updated-episode.mp3', 1200, 'audio/mpeg');

        $response = $this->put("/dashboard/episodes/{$episode->id}", [
            'title' => 'Updated Episode',
            'description' => 'Updated description',
            'published_date' => '2024-03-23',
            'audio_file' => $newFile,
        ]);

        $response->assertRedirect(route('episodes.dashboard'));

        // Refresh episode from database
        $episode->refresh();

        $this->assertEquals('Updated Episode', $episode->title);

        // Check that the URL was updated and indicates R2 storage
        $this->assertNotNull($episode->url);
        $this->assertStringContainsString('updated-episode.mp3', $episode->url);

        // Check new file was uploaded to R2
        // The filename will have a timestamp prefix, so we need to find the actual stored file
        $files = Storage::disk('r2')->files('episodes');
        $testFile = collect($files)->first(function ($file) {
            return str_contains($file, 'updated-episode.mp3');
        });

        $this->assertNotNull($testFile, 'Expected updated file not found in R2 storage');
        $this->assertTrue(Storage::disk('r2')->exists($testFile));
    }

    public function test_episode_deletion_removes_file_from_r2()
    {
        // Create and authenticate a user
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        // Create R2 episode with a proper R2 URL
        $episode = Episode::create([
            'title' => 'R2 Episode to Delete',
            'filename' => 'delete-test.mp3',
            'url' => 'https://r2.example.com/episodes/delete-test.mp3',
            'duration' => '02:30',
            'file_size' => '3.5 MB',
            'format' => 'MP3',
            'published_date' => '2024-03-23',
            'description' => 'Episode to be deleted',
        ]);

        // Upload fake file to R2 (simulate the file exists)
        $storagePath = 'episodes/delete-test.mp3';
        Storage::disk('r2')->put($storagePath, 'fake audio content');
        $this->assertTrue(Storage::disk('r2')->exists($storagePath));

        $response = $this->delete("/dashboard/episodes/{$episode->id}");

        $response->assertStatus(200);

        // Check episode was deleted from database
        $this->assertDatabaseMissing('episodes', ['id' => $episode->id]);

        // Check file was deleted from R2
        $this->assertFalse(Storage::disk('r2')->exists($storagePath));
    }
}
