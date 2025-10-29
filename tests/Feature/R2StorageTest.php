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

        // Set R2 as default storage and configure R2
        config(['filesystems.default' => 'r2']);
        config(['filesystems.disks.r2.key' => 'test-key']);
        config(['filesystems.disks.r2.bucket' => 'test-bucket']);

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
        $this->assertEquals('r2', $episode->storage_disk);
        $this->assertStringStartsWith('episodes/', $episode->storage_path);

        // Check file was uploaded to R2
        Storage::disk('r2')->assertExists($episode->storage_path);
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
        $this->assertEquals('local', $episode->storage_disk);
        $this->assertStringStartsWith('audios/', $episode->storage_path);

        // Check file was uploaded to local storage
        $this->assertTrue(file_exists(public_path($episode->storage_path)));
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
            'storage_disk' => 'local',
        ]);

        // Set R2 as default storage and configure R2
        config(['filesystems.default' => 'r2']);
        config(['filesystems.disks.r2.key' => 'test-key']);
        config(['filesystems.disks.r2.bucket' => 'test-bucket']);

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
        $this->assertEquals('r2', $episode->storage_disk);
        $this->assertStringStartsWith('episodes/', $episode->storage_path);

        // Check new file was uploaded to R2
        Storage::disk('r2')->assertExists($episode->storage_path);
    }

    public function test_episode_deletion_removes_file_from_r2()
    {
        // Create and authenticate a user
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        // Create R2 episode
        $episode = Episode::create([
            'title' => 'R2 Episode to Delete',
            'filename' => 'delete-test.mp3',
            'url' => 'https://r2.example.com/episodes/delete-test.mp3',
            'duration' => '02:30',
            'file_size' => '3.5 MB',
            'format' => 'MP3',
            'published_date' => '2024-03-23',
            'description' => 'Episode to be deleted',
            'storage_disk' => 'r2',
            'storage_path' => 'episodes/delete-test.mp3',
        ]);

        // Upload fake file to R2
        Storage::disk('r2')->put($episode->storage_path, 'fake audio content');
        Storage::disk('r2')->assertExists($episode->storage_path);

        $response = $this->delete("/dashboard/episodes/{$episode->id}");

        $response->assertStatus(200);

        // Check episode was deleted from database
        $this->assertDatabaseMissing('episodes', ['id' => $episode->id]);

        // Check file was deleted from R2
        Storage::disk('r2')->assertMissing($episode->storage_path);
    }

    public function test_migration_command_dry_run()
    {
        // Create local episodes
        Episode::create([
            'title' => 'Local Episode 1',
            'filename' => 'local1.mp3',
            'url' => '/audios/local1.mp3',
            'duration' => '03:00',
            'file_size' => '4.0 MB',
            'format' => 'MP3',
            'published_date' => '2024-03-20',
            'description' => 'Local episode 1',
            'storage_disk' => 'local',
        ]);

        Episode::create([
            'title' => 'Local Episode 2',
            'filename' => 'local2.mp3',
            'url' => '/audios/local2.mp3',
            'duration' => '03:30',
            'file_size' => '4.5 MB',
            'format' => 'MP3',
            'published_date' => '2024-03-21',
            'description' => 'Local episode 2',
            'storage_disk' => 'local',
        ]);

        // Mock R2 configuration
        config([
            'filesystems.disks.r2.key' => 'test-key',
            'filesystems.disks.r2.bucket' => 'test-bucket',
        ]);

        $this->artisan('episodes:migrate-to-r2', ['--dry-run' => true])
            ->expectsOutput('Starting episode migration to Cloudflare R2...')
            ->expectsOutput('Found 2 episodes to migrate.')
            ->expectsOutput('DRY RUN - No files will be actually migrated:')
            ->assertExitCode(0);
    }
}