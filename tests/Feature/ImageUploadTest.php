<?php

namespace Tests\Feature;

use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

class ImageUploadTest extends TestCase
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
    public function image_can_be_uploaded(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $this->response = $this->post('/api/gallery', [
            'title' => 'Test Image',
            'description' => 'Test Description',
            'image' => $file,
        ]);

        $this->response->assertStatus(201);
        $this->response->assertJson([
            'success' => true,
            'message' => 'Image uploaded successfully',
        ]);

        $this->response->assertJsonFragment([
            'title' => 'Test Image',
            'description' => 'Test Description',
        ]);

        $responseData = $this->response->json('data');
        // $this->assertTrue(Storage::disk('s3')->exists($responseData['path']));
        Storage::disk('s3')->assertExists($responseData['path']);
    }

    #[Test]
    public function image_upload_validates_required_fields(): void
    {
        $this->response = $this->post('/api/gallery', []);

        $this->response->assertStatus(422);
        $this->response->assertJson([
            'success' => false,
            'message' => 'Validation failed',
        ]);

        $this->response->assertJsonFragment([
            'title' => [
                'The title field is required.',
            ],
        ]);

        $this->response->assertJsonFragment([
            'image' => [
                'The image field is required.',
            ],
        ]);
    }

    #[Test]
    public function image_upload_validates_file_type(): void
    {
        $file = UploadedFile::fake()->image('test.pdf', 100, 100);

        $this->response = $this->post('/api/gallery', [
            'title' => 'Test Image',
            'description' => 'Test Description',
            'image' => $file,
        ]);

        $this->response->assertStatus(422)
            ->assertJsonFragment([
                'success' => false,
            ]);
    }

    #[Test]
    public function image_upload_returns_correct_data_structure(): void
    {
        $file = UploadedFile::fake()->image('mime.jpg', 100, 100);

        $this->response = $this->post('/api/gallery', [
            'title' => 'Test Image',
            'description' => 'Test Description',
            'image' => $file,
        ]);

        $this->response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'title',
                    'description',
                    'path',
                    'url',
                    'original_name',
                ],
            ]);

        $this->response->assertJson([
            'data' => [
                'title' => 'Test Image',
                'description' => 'Test Description',
            ]
        ]);
    }

    #[Test]
    public function image_upload_stores_file_correctly(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $this->response = $this->post('/api/gallery', [
            'title' => 'Test Image',
            'description' => 'Test Description',
            'image' => $file,
        ]);

        $this->response->assertStatus(201);

        $uploadedImage = Image::where('title', 'Test Image')->first();

        $this->assertNotNull($uploadedImage, 'Image should be stored in database');
        $this->assertEquals('Test Image', $uploadedImage->title);
        $this->assertEquals('Test Description', $uploadedImage->description);
        $this->assertNotEmpty($uploadedImage->path);
        $this->assertNotEmpty($uploadedImage->url);

        // $this->assertTrue(Storage::disk('s3')->exists($uploadedImage->path));
        Storage::disk('s3')->assertExists($uploadedImage->path);
    }

    #[Test]
    public function image_can_be_uploaded_to_s3_disk(): void
    {
        $file = UploadedFile::fake()->image('test-s3.jpg', 100, 100);

        $this->response = $this->post('/api/gallery', [
            'title' => 'Test S3 Image',
            'description' => 'Test Description',
            'image' => $file,
        ]);

        $this->response->assertStatus(201);

        $this->response->assertJson([
            'success' => true,
            'message' => 'Image uploaded successfully',
        ]);

        $responseData = $this->response->json('data');

        // $this->assertTrue(Storage::disk('s3')->exists($responseData['path']));
        Storage::disk('s3')->assertExists($responseData['path']);

        $this->assertNotEmpty($responseData['url']);
        $this->assertStringStartsWith('http', $responseData['url']);

        $image = Image::find($responseData['id']);
        $this->assertNotNull($image);
        $this->assertEquals('Test S3 Image', $image->title);
        $this->assertStringStartsWith('images/', $image->path);
    }

    #[Test]
    public function s3_image_url_is_generated_in_index_view(): void
    {
        $file = UploadedFile::fake()->image('test-s3.jpg', 100, 100);
        $path = Storage::disk('s3')->putFile('images', $file);

        $image = Image::create([
            'title' => 'Test Image',
            'description' => 'Test Description',
            'path' => $path,
            'url' => 'https://s3.amazonaws.com/bucket/' . $path,
            'original_name' => 'test-s3.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $this->response = $this->get('/');

        $this->response->assertStatus(200);
        $this->response->assertViewHas('images');

        $images = $this->response->viewData('images');
        $foundImage = $images->firstWhere('id', $image->id);
        $this->assertNotNull($foundImage);
        $this->assertNotEmpty($foundImage->url);
    }

    #[Test]
    public function s3_image_url_is_generated_in_show_view(): void
    {
        $file = UploadedFile::fake()->image('test-s3.jpg', 100, 100);
        $path = Storage::disk('s3')->putFile('images', $file);

        $image = Image::create([
            'title' => 'Test Image',
            'description' => 'Test Description',
            'path' => $path,
            'url' => 'https://s3.amazonaws.com/bucket/' . $path,
            'original_name' => 'test-s3.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $this->response = $this->get("/gallery/{$image->id}");

        $this->response->assertStatus(200);
        $this->response->assertViewHas('image');

        $viewImage = $this->response->viewData('image');
        $this->assertNotEmpty($viewImage->url);
        $this->assertStringStartsWith('http', $viewImage->url);
    }
}