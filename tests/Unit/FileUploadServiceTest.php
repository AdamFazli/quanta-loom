<?php

namespace Tests\Unit;

use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use App\Services\FileUploadService;

class FileUploadServiceTest extends TestCase
{
    protected FileUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FileUploadService();

        Storage::fake('local');
        Storage::fake('public');
        Storage::fake('s3');
    }

    #[Test]
    public function upload_file_to_public_disk(): void
    {

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $path = $this->service->upload($file, 'public', 'images');

        // $this->assertTrue(Storage::disk('public')->exists($path));
        Storage::disk('public')->assertExists($path);

        $files = Storage::disk('public')->files('images');
        $this->assertContains($path, $files);

        $this->assertStringStartsWith('images/', $path);
        $this->assertStringEndsWith('test.jpg', $path);
    }

    #[Test]
    public function upload_file_to_local_disk(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $path = $this->service->upload($file, 'local', 'images');

        // $this->assertTrue(Storage::disk('local')->exists($path));
        Storage::disk('local')->assertExists($path);

        $this->assertStringStartsWith('images/', $path);
        $this->assertStringEndsWith('test.jpg', $path);
    }

    #[Test]
    public function it_generates_correct_url_for_public_disk(): void
    {
        $path = 'images/test.jpg';

        $url = $this->service->url($path, 'public');

        $expectedUrl = Storage::disk('public')->url($path);
        $this->assertEquals($expectedUrl, $url);

        $this->assertStringContainsString('storage/' . $path, $url);
    }

    #[Test]
    public function it_can_delete_file_from_storage(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);
        $path = $this->service->upload($file, 'public', 'images');

        // $this->assertTrue(Storage::disk('public')->exists($path));
        Storage::disk('public')->assertExists($path);
        
        $result = $this->service->delete($path, 'public');
        $this->assertTrue($result);

        // $this->assertFalse(Storage::disk('public')->exists($path));
        Storage::disk('public')->assertMissing($path);
    }

    #[Test]
    public function it_can_delete_file_from_local_disk(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);
        $path = $this->service->upload($file, 'local', 'images');

        // $this->assertTrue(Storage::disk('local')->exists($path));
        Storage::disk('local')->assertExists($path);

        $result = $this->service->delete($path, 'local');
        $this->assertTrue($result);

        // $this->assertFalse(Storage::disk('local')->exists($path));
        Storage::disk('local')->assertMissing($path);
    }

    public function it_can_upload_multiple_files(): void
    {
        $file1 = UploadedFile::fake()->image('test1.jpg', 100, 100);
        $file2 = UploadedFile::fake()->image('test2.jpg', 100, 100);

        $path1 = $this->service->upload($file1, 'public', 'images');
        $path2 = $this->service->upload($file2, 'public', 'images');

        // $this->assertTrue(Storage::disk('public')->exists($path1));
        // $this->assertTrue(Storage::disk('public')->exists($path2));
        Storage::disk('public')->assertExists($path1);
        Storage::disk('public')->assertExists($path2);

        $files = Storage::disk('public')->files('images');
        $this->assertCount(2, $files);
    }

    #[Test]
    public function it_can_upload_file_to_s3_disk(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);
        $path = $this->service->upload($file, 's3', 'images');

        // $this->assertTrue(Storage::disk('s3')->exists($path));
        Storage::disk('s3')->assertExists($path);

        $files = Storage::disk('s3')->files('images');
        $this->assertContains($path, $files);

        $this->assertStringStartsWith('images/', $path);
        $this->assertStringEndsWith('test.jpg', $path);
    }

    #[Test]
    public function it_generates_temporary_url_with_custom_expiration_for_s3(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);
        $path = $this->service->upload($file, 's3', 'images');

        $url = $this->service->url($path, 's3');

        $this->assertNotEmpty($url);
        $this->assertIsString($url);
        $this->assertStringStartsWith('http', $url); // Fake storage uses http://localhost
    }

    #[Test]
    public function it_can_delete_file_from_s3_disk(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);
        $path = $this->service->upload($file, 's3', 'images');

        // $this->assertTrue(Storage::disk('s3')->exists($path));
        Storage::disk('s3')->assertExists($path);

        $result = $this->service->delete($path, 's3');
        $this->assertTrue($result);

        // $this->assertFalse(Storage::disk('s3')->exists($path));
        Storage::disk('s3')->assertMissing($path);
    }

    #[Test]
    public function it_handles_s3_url_generation_differently_from_public_disk(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);
        $path = $this->service->upload($file, 's3', 'images');

        $s3Url = $this->service->url($path, 's3');
        $publicUrl = $this->service->url($path, 'public');

        $this->assertNotEquals($s3Url, $publicUrl);
        $this->assertStringStartsWith('http', $s3Url); // Fake storage uses http://localhost
        $this->assertStringContainsString('expiration', $s3Url); // S3 URLs have expiration parameter
    }
}
