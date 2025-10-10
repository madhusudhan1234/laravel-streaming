<?php

namespace Tests\Unit;

use App\Http\Controllers\EpisodeController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EpisodeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new EpisodeController();
        
        // Create test episodes data
        $this->createTestEpisodesFile();
    }

    protected function tearDown(): void
    {
        // Clean up test file
        $episodesPath = storage_path('app/episodes.json');
        if (file_exists($episodesPath)) {
            unlink($episodesPath);
        }
        
        parent::tearDown();
    }

    private function createTestEpisodesFile()
    {
        $testData = [
            'episodes' => [
                [
                    'id' => 1,
                    'title' => 'Test Episode 1',
                    'filename' => 'test-episode-1.mp3',
                    'url' => '/audios/test-episode-1.mp3'
                ],
                [
                    'id' => 2,
                    'title' => 'Test Episode 2',
                    'filename' => 'test-episode-2.m4a',
                    'url' => '/audios/test-episode-2.m4a'
                ]
            ]
        ];

        file_put_contents(storage_path('app/episodes.json'), json_encode($testData));
    }

    public function test_get_episodes_returns_array_of_episodes()
    {
        $episodes = $this->controller->getEpisodes();

        $this->assertIsArray($episodes);
        $this->assertCount(2, $episodes);
        $this->assertEquals('Test Episode 1', $episodes[0]['title']);
        $this->assertEquals('Test Episode 2', $episodes[1]['title']);
    }

    public function test_get_episodes_returns_empty_array_when_file_not_exists()
    {
        // Remove the test file
        unlink(storage_path('app/episodes.json'));

        $episodes = $this->controller->getEpisodes();

        $this->assertIsArray($episodes);
        $this->assertEmpty($episodes);
    }

    public function test_api_index_returns_json_response()
    {
        $response = $this->controller->apiIndex();

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('episodes', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertEquals(2, $data['total']);
    }

    public function test_show_returns_episode_by_id()
    {
        $response = $this->controller->show(1);

        $this->assertEquals(200, $response->getStatusCode());
        
        $episode = json_decode($response->getContent(), true);
        $this->assertEquals(1, $episode['id']);
        $this->assertEquals('Test Episode 1', $episode['title']);
    }

    public function test_show_returns_404_for_non_existent_episode()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Episode not found');

        $this->controller->show(999);
    }

    public function test_index_returns_inertia_response()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Home')
                 ->has('episodes', 2)
        );
    }

    public function test_api_episodes_endpoint()
    {
        $response = $this->get('/api/episodes');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'episodes' => [
                '*' => ['id', 'title', 'filename', 'url']
            ],
            'total'
        ]);
    }

    public function test_episode_show_endpoint()
    {
        $response = $this->get('/api/episodes/1');

        $response->assertStatus(200);
        $response->assertJsonStructure(['id', 'title', 'filename', 'url']);
    }

    public function test_episode_show_endpoint_returns_404_for_invalid_id()
    {
        $response = $this->get('/api/episodes/999');

        $response->assertStatus(404);
    }
}