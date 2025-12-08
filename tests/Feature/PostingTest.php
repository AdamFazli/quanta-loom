<?php

namespace Tests\Feature;

use App\Models\Posting;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostingTest extends TestCase
{
    use RefreshDatabase;

    protected $response;
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    #[Test]
    public function posting_can_be_created_with_multiple_images(): void
    {
        $files = [
            UploadedFile::fake()->image('image1.jpg', 100, 100),
            UploadedFile::fake()->image('image2.jpg', 100, 100),
            UploadedFile::fake()->image('image3.jpg', 100, 100),
        ];

        $this->response = $this->postJson('/api/postings', [
            'title' => 'Test Posting',
            'description' => 'Test Description',
            'images' => $files,
        ]);

        $this->response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Posting created successfully',
            ]);

        $data = $this->response->json('data');
        $this->assertCount(3, $data['images']);

        $posting = Posting::find($data['id']);
        $this->assertNotNull($posting);
        $this->assertEquals('Test Posting', $posting->title);
        $this->assertCount(3, $posting->images);

        foreach ($posting->images as $image) {
            // $this->assertTrue(Storage::disk('s3')->exists($image->path));
            Storage::disk('s3')->assertExists($image->path);
        }
    }

    #[Test]
    public function individual_image_can_be_deleted_from_posting(): void
    {
        $posting = Posting::create([
            'title' => 'Test Posting',
            'description' => 'Test',
        ]);

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);
        $path = Storage::disk('s3')->putFile('images', $file);

        $image = Image::create([
            'posting_id' => $posting->id,
            'title' => 'Test Image',
            'path' => $path,
            'url' => 'https://s3.amazonaws.com/bucket/' . $path,
            'original_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        // $this->assertTrue(Storage::disk('s3')->exists($path));
        Storage::disk('s3')->assertExists($path);

        $this->response = $this->deleteJson("/api/postings/{$posting->id}/images/{$image->id}");

        $this->response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Image deleted successfully',
            ]);

        // $this->assertFalse(Storage::disk('s3')->exists($path));
        Storage::disk('s3')->assertMissing($path);

        $this->assertDatabaseHas('postings', ['id' => $posting->id]);
        $this->assertDatabaseMissing('images', ['id' => $image->id]);
    }

    #[Test]
    public function all_images_can_be_deleted_from_posting(): void
    {
        $posting = Posting::create([
            'title' => 'Test Posting',
            'description' => 'Test',
        ]);

        $paths = [];
        for ($i = 1; $i <= 3; $i++) {
            $file = UploadedFile::fake()->image("test{$i}.jpg", 100, 100);
            $path = Storage::disk('s3')->putFile('images', $file);
            $paths[] = $path;

            Image::create([
                'posting_id' => $posting->id,
                'title' => "Test Image {$i}",
                'path' => $path,
                'url' => 'https://s3.amazonaws.com/bucket/' . $path,
                'original_name' => "test{$i}.jpg",
                'mime_type' => 'image/jpeg',
                'size' => 1024,
            ]);
        }

        $this->response = $this->deleteJson("/api/postings/{$posting->id}/images");

        $this->response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'All images deleted successfully',
            ]);

        foreach ($paths as $path) {
            $this->assertFalse(Storage::disk('s3')->exists($path));
        }

        $this->assertDatabaseHas('postings', ['id' => $posting->id]);
        $this->assertEquals(0, $posting->fresh()->images()->count());
    }

    #[Test]
    public function deleting_posting_deletes_all_images_from_s3(): void
    {
        $posting = Posting::create([
            'title' => 'Test Posting',
            'description' => 'Test',
        ]);

        $paths = [];
        for ($i = 1; $i <= 3; $i++) {
            $file = UploadedFile::fake()->image("test{$i}.jpg", 100, 100);
            $path = Storage::disk('s3')->putFile('images', $file);
            $paths[] = $path;

            Image::create([
                'posting_id' => $posting->id,
                'title' => "Test Image {$i}",
                'path' => $path,
                'url' => 'https://s3.amazonaws.com/bucket/' . $path,
                'original_name' => "test{$i}.jpg",
                'mime_type' => 'image/jpeg',
                'size' => 1024,
            ]);
        }

        foreach ($paths as $path) {
            $this->assertTrue(Storage::disk('s3')->exists($path));
        }

        $this->response = $this->deleteJson("/api/postings/{$posting->id}");

        $this->response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Posting deleted successfully',
            ]);

        foreach ($paths as $path) {
            $this->assertFalse(Storage::disk('s3')->exists($path));
        }

        $this->assertDatabaseMissing('postings', ['id' => $posting->id]);
        $this->assertEquals(0, Image::where('posting_id', $posting->id)->count());
    }

    #[Test]
    public function posting_edit_page_can_be_accessed(): void
    {
        $posting = Posting::create([
            'title' => 'Test Posting',
            'description' => 'Test',
        ]);

        $this->response = $this->get("/postings/{$posting->id}/edit");

        $this->response->assertStatus(200)
            ->assertViewHas('posting')
            ->assertSee('Edit Posting')
            ->assertSee($posting->title);
    }

    #[Test]
    public function posting_title_and_description_can_be_updated(): void
    {
        $posting = Posting::create([
            'title' => 'Test Posting',
            'description' => 'Test',
        ]);

        $this->response = $this->put("/postings/{$posting->id}", [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ]);

        $this->response->assertRedirect(route('postings.show', $posting->id));
        $this->response->assertSessionHas('success', 'Posting updated successfully!');

        $posting->refresh();
        $this->assertEquals('Updated Title', $posting->title);
        $this->assertEquals('Updated Description', $posting->description);
    }

    #[Test]
    public function images_can_be_added_to_existing_posting(): void
    {
        $posting = Posting::create([
            'title' => 'Test Posting',
            'description' => 'Test',
        ]);

        $file1 = UploadedFile::fake()->image('image1.jpg', 100, 100);
        $path1 = Storage::disk('s3')->putFile('images', $file1);
        Image::create([
            'posting_id' => $posting->id,
            'title' => 'Image 1',
            'path' => $path1,
            'url' => 'https://s3.amazonaws.com/bucket/' . $path1,
            'original_name' => 'image1.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $newFiles = [
            UploadedFile::fake()->image('image2.jpg', 100, 100),
            UploadedFile::fake()->image('image3.jpg', 100, 100),
        ];

        $this->response = $this->put("/postings/{$posting->id}", [
            'title' => $posting->title,
            'description' => $posting->description,
            'images' => $newFiles,
        ]);

        $this->response->assertRedirect(route('postings.show', $posting->id));

        $this->assertEquals(3, $posting->fresh()->images()->count());
    }

    #[Test]
    public function images_can_be_deleted_from_existing_posting(): void
    {
        $posting = Posting::create([
            'title' => 'Test Posting',
            'description' => 'Test',
        ]);

        $paths = [];
        $imageIds = [];
        for ($i = 1; $i <= 3; $i++) {
            $file = UploadedFile::fake()->image("test{$i}.jpg", 100, 100);
            $path = Storage::disk('s3')->putFile('images', $file);
            $paths[] = $path;

            $image = Image::create([
                'posting_id' => $posting->id,
                'title' => "Test Image {$i}",
                'path' => $path,
                'url' => 'https://s3.amazonaws.com/bucket/' . $path,
                'original_name' => "test{$i}.jpg",
                'mime_type' => 'image/jpeg',
                'size' => 1024,
            ]);
            $imageIds[] = $image->id;
        }
        
        $this->response = $this->put("/postings/{$posting->id}", [
            'title' => $posting->title,
            'description' => $posting->description,
            'delete_images' => [$imageIds[0], $imageIds[1]],
        ]);

        $this->response->assertRedirect(route('postings.show', $posting->id));

        $this->assertEquals(1, $posting->fresh()->images()->count());

        Storage::disk('s3')->assertMissing($paths[0]);
        Storage::disk('s3')->assertMissing($paths[1]);
        Storage::disk('s3')->assertExists($paths[2]);
    }

    #[Test]
    public function posting_can_be_updated_with_new_images_and_deleted_images(): void
    {
        $posting = Posting::create([
            'title' => 'Test Posting',
            'description' => 'Test',
        ]);

        $initialPaths = [];
        $imageIds = [];
        for ($i = 1; $i <= 2; $i++) {
            $file = UploadedFile::fake()->image("test{$i}.jpg", 100, 100);
            $path = Storage::disk('s3')->putFile('images', $file);
            $initialPaths[] = $path;

            $image = Image::create([
                'posting_id' => $posting->id,
                'title' => "Test Image {$i}",
                'path' => $path,
                'url' => 'https://s3.amazonaws.com/bucket/' . $path,
                'original_name' => "test{$i}.jpg",
                'mime_type' => 'image/jpeg',
                'size' => 1024,
            ]);
            $imageIds[] = $image->id;
        }
        
        $newFiles = [
            UploadedFile::fake()->image('image3.jpg', 100, 100),
            UploadedFile::fake()->image('image4.jpg', 100, 100),
        ];

        $this->response = $this->put("/postings/{$posting->id}", [
            'title' => $posting->title,
            'description' => $posting->description,
            'images' => $newFiles,
            'delete_images' => [$imageIds[0]],
        ]);

        $this->response->assertRedirect(route('postings.show', $posting->id));

        $posting->refresh();
        $this->assertEquals(3, $posting->images()->count());
        $this->assertEquals('Test Posting', $posting->title);
        $this->assertEquals('Test', $posting->description);

        Storage::disk('s3')->assertMissing($initialPaths[0]);
        Storage::disk('s3')->assertExists($initialPaths[1]);
    }

    #[Test]
    public function posting_update_required_fields(): void
    {
        $posting = Posting::create([
            'title' => 'Test Posting',
            'description' => 'Test',
        ]);

        $this->response = $this->put("/postings/{$posting->id}", [
            'title' => '',
        ]);
        
        $this->response->assertSessionHasErrors('title');
        $this->response->assertRedirect(route('postings.edit', $posting->id));
    }
}