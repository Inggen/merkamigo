<?php

namespace Tests\Feature\Support;

use App\Support\Media\MediaUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 1.2 del TODO: "optimizar, comprimir y generar variantes de imágenes".
 */
class MediaUploaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_image_wider_than_the_context_limit_is_scaled_down(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo.jpg', 2000, 1000);

        $path = app(MediaUploader::class)->store($file, 'avatar', 'test');

        [$width, $height] = getimagesize(Storage::disk('public')->path($path));

        $this->assertSame(512, $width);
        $this->assertSame(256, $height);
    }

    public function test_an_image_smaller_than_the_context_limit_is_not_upscaled(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo.jpg', 200, 100);

        $path = app(MediaUploader::class)->store($file, 'avatar', 'test');

        [$width, $height] = getimagesize(Storage::disk('public')->path($path));

        $this->assertSame(200, $width);
        $this->assertSame(100, $height);
    }

    public function test_an_image_is_converted_to_webp_when_the_context_requests_it(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo.png', 2000, 2000);

        $path = app(MediaUploader::class)->store($file, 'product_photo', 'test');

        $this->assertSame('webp', pathinfo($path, PATHINFO_EXTENSION));
        $this->assertSame('image/webp', Storage::disk('public')->mimeType($path));

        [$width] = getimagesize(Storage::disk('public')->path($path));

        $this->assertSame(1000, $width);
    }

    public function test_a_context_without_max_width_stores_the_file_unmodified(): void
    {
        Storage::fake('private');

        $file = UploadedFile::fake()->create('documento.pdf', 500, 'application/pdf');

        $path = app(MediaUploader::class)->store($file, 'verification_document', 'test');

        Storage::disk('private')->assertExists($path);
        $this->assertSame('pdf', pathinfo($path, PATHINFO_EXTENSION));
    }

    public function test_a_document_marked_private_never_lands_on_the_public_disk(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        $file = UploadedFile::fake()->create('documento.pdf', 500, 'application/pdf');

        $path = app(MediaUploader::class)->store($file, 'verification_document', 'test');

        Storage::disk('public')->assertMissing($path);
    }
}
