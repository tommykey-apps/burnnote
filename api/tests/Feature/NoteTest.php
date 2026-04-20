<?php

namespace Tests\Feature;

use App\Services\NoteRepository;
use Mockery;
use Tests\TestCase;

class NoteTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_store_creates_note(): void
    {
        $repo = Mockery::mock(NoteRepository::class);
        $repo->shouldReceive('put')
            ->once()
            ->with('ciphertext1', 'iv1234567890abcde', Mockery::type('int'))
            ->andReturn('abc123xyz0000000');
        $this->app->instance(NoteRepository::class, $repo);

        $response = $this->postJson('/api/notes', [
            'ciphertext' => 'ciphertext1',
            'iv'         => 'iv1234567890abcde',
            'expires_in' => 3600,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'expires_at'])
            ->assertJson(['id' => 'abc123xyz0000000']);
    }

    public function test_store_rejects_invalid_ciphertext(): void
    {
        $response = $this->postJson('/api/notes', [
            'ciphertext' => 'not base64!!!',
            'iv'         => 'iv1234567890abcde',
            'expires_in' => 3600,
        ]);

        $response->assertStatus(422);
    }

    public function test_store_rejects_too_long_expiry(): void
    {
        $response = $this->postJson('/api/notes', [
            'ciphertext' => 'ciphertext1',
            'iv'         => 'iv1234567890abcde',
            'expires_in' => 99999999,
        ]);

        $response->assertStatus(422);
    }

    public function test_exists_returns_200_when_found(): void
    {
        $repo = Mockery::mock(NoteRepository::class);
        $repo->shouldReceive('peek')
            ->once()
            ->with('abc123xyz0000000')
            ->andReturn(['ciphertext' => 'c', 'iv' => 'i', 'expires_at' => time() + 3600]);
        $this->app->instance(NoteRepository::class, $repo);

        $response = $this->getJson('/api/notes/abc123xyz0000000/exists');

        $response->assertStatus(200)->assertJson(['exists' => true]);
    }

    public function test_exists_returns_404_when_missing(): void
    {
        $repo = Mockery::mock(NoteRepository::class);
        $repo->shouldReceive('peek')->once()->andReturn(null);
        $this->app->instance(NoteRepository::class, $repo);

        $response = $this->getJson('/api/notes/abc123xyz0000000/exists');

        $response->assertStatus(404);
    }

    public function test_consume_returns_ciphertext_and_deletes(): void
    {
        $repo = Mockery::mock(NoteRepository::class);
        $repo->shouldReceive('consume')
            ->once()
            ->with('abc123xyz0000000')
            ->andReturn(['ciphertext' => 'cipher', 'iv' => 'iv1']);
        $this->app->instance(NoteRepository::class, $repo);

        $response = $this->getJson('/api/notes/abc123xyz0000000');

        $response->assertStatus(200)
            ->assertJson(['ciphertext' => 'cipher', 'iv' => 'iv1']);
    }

    public function test_consume_returns_410_when_already_gone(): void
    {
        $repo = Mockery::mock(NoteRepository::class);
        $repo->shouldReceive('consume')->once()->andReturn(null);
        $this->app->instance(NoteRepository::class, $repo);

        $response = $this->getJson('/api/notes/abc123xyz0000000');

        $response->assertStatus(410);
    }

    public function test_invalid_id_format_returns_404(): void
    {
        $response = $this->getJson('/api/notes/short/exists');
        $response->assertStatus(404);
    }
}
