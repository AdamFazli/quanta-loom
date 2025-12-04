<?php

namespace Tests\Feature;

use App\Models\Image;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected $response;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('s3');

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->withHeaders([
            'Accept' => 'application/json',
        ]);
    }

    #[Test]
    public function image_can_be_deleted(): void
    {
        $file = UploadedFile::fake()->image('test-image.jpg', 100, 100);
        $path = Storage::disk('s3')->putFile('images', $file);

        $image = Image::create([
            'title' => 'Test Image',
            'description' => 'Test Description',
            'path' => $path,
            'url' => 'https://s3.amazonaws.com/bucket/' . $path,
            'original_name' => 'test-image.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $this->assertDatabaseHas('images', [
            'id' => $image->id
        ]);

        // $this->assertTrue(Storage::disk('s3')->exists($path));
        Storage::disk('s3')->assertExists($path);

        $this->response = $this->deleteJson("/api/gallery/{$image->id}");

        $this->response->assertStatus(200);
        $this->response->assertJson([
            'success' => true,
            'message' => 'Image deleted successfully',
        ]);

        $this->assertDatabaseMissing('images', [
            'id' => $image->id
        ]);

        Storage::disk('s3')->assertMissing($path);
    }

    #[Test]
    public function image_can_be_deleted_from_web(): void
    {
        $file = UploadedFile::fake()->image('test-image.jpg', 100, 100);
        $path = Storage::disk('s3')->putFile('images', $file);

        $image = Image::create([
            'title' => 'Test Image',
            'description' => 'Test Description',
            'path' => $path,
            'url' => 'https://s3.amazonaws.com/bucket/' . $path,
            'original_name' => 'test-image.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $this->assertDatabaseHas('images', [
            'id' => $image->id
        ]);

        // $this->assertTrue(Storage::disk('s3')->exists($path));
        Storage::disk('s3')->assertExists($path);

        $this->response = $this->delete("/gallery/{$image->id}");

        $this->response->assertRedirect(route('images.index'));
        $this->response->assertSessionHas('success', 'Image deleted successfully!');

        $this->assertDatabaseMissing('images', [
            'id' => $image->id
        ]);

        Storage::disk('s3')->assertMissing($path);
    }

    #[Test]
    public function deleting_image_removes_file_from_s3(): void
    {
        $file = UploadedFile::fake()->image('test-image.jpg', 100, 100);
        $path = Storage::disk('s3')->putFile('images', $file);

        $image = Image::create([
            'title' => 'Test Image',
            'description' => 'Test Description',
            'path' => $path,
            'url' => 'https://s3.amazonaws.com/bucket/' . $path,
            'original_name' => 'test-image.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        // $this->assertTrue(Storage::disk('s3')->exists($path));
        Storage::disk('s3')->assertExists($path);

        $this->response = $this->deleteJson("/api/gallery/{$image->id}");

        $this->response->assertStatus(200);

        Storage::disk('s3')->assertMissing($path);
    }

    #[Test]
    public function delete_api_returns_correct_json_structure(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);
        $path = Storage::disk('s3')->putFile('images', $file);

        $image = Image::create([
            'title' => 'Test Image',
            'description' => 'Test Description',
            'path' => $path,
            'url' => 'https://s3.amazonaws.com/bucket/' . $path,
            'original_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $this->response = $this->deleteJson("/api/gallery/{$image->id}");

        $this->response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->response->assertJson([
            'success' => true,
            'message' => 'Image deleted successfully',
        ]);
    }
}
