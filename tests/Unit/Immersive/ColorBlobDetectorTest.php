<?php

namespace Tests\Unit\Immersive;

use App\Domain\Immersive\Support\ColorBlobDetector;
use GdImage;
use PHPUnit\Framework\TestCase;

/**
 * IMM-013 del TODO inmersivo (redefinido). Genera imágenes sintéticas con
 * GD en vez de depender de un archivo de ejemplo, para que el test sea
 * determinista y no dependa de una imagen real subida a mano.
 */
class ColorBlobDetectorTest extends TestCase
{
    private string $tempPath;

    protected function tearDown(): void
    {
        if (isset($this->tempPath) && file_exists($this->tempPath)) {
            unlink($this->tempPath);
        }

        parent::tearDown();
    }

    public function test_it_detects_distinct_legend_colors_ignoring_background_and_text(): void
    {
        $image = $this->blankCanvas(200, 200);

        $cyan = imagecolorallocate($image, 30, 200, 200);
        imagefilledrectangle($image, 20, 20, 45, 45, $cyan);

        $pink = imagecolorallocate($image, 240, 150, 150);
        imagefilledellipse($image, 150, 150, 30, 30, $pink);

        $black = imagecolorallocate($image, 0, 0, 0);
        imagestring($image, 5, 60, 60, 'S1', $black);

        $this->tempPath = $this->savePng($image);

        $colors = (new ColorBlobDetector)->detectDistinctColors($this->tempPath);
        $hexes = array_column($colors, 'color_hex');

        $this->assertCount(2, $colors, 'Debe detectar exactamente los 2 colores de la leyenda, sin el fondo ni el texto.');
        $this->assertContains('#1ec8c8', $hexes);
        $this->assertContains('#f09696', $hexes);
    }

    public function test_it_finds_the_centroid_of_each_target_color_blob_on_a_plan(): void
    {
        $image = $this->blankCanvas(200, 200);

        $cyan = imagecolorallocate($image, 30, 200, 200);
        imagefilledrectangle($image, 20, 20, 45, 45, $cyan);

        $this->tempPath = $this->savePng($image);

        $blobs = (new ColorBlobDetector)->detectBlobsForColors($this->tempPath, ['#1ec8c8']);

        $this->assertCount(1, $blobs);
        $this->assertEqualsWithDelta(32.5, $blobs[0]['x'], 3.0);
        $this->assertEqualsWithDelta(32.5, $blobs[0]['y'], 3.0);
    }

    public function test_it_separates_two_blobs_of_different_colors(): void
    {
        $image = $this->blankCanvas(300, 300);

        $cyan = imagecolorallocate($image, 30, 200, 200);
        imagefilledrectangle($image, 20, 20, 50, 50, $cyan);

        $green = imagecolorallocate($image, 40, 140, 40);
        imagefilledrectangle($image, 200, 200, 230, 230, $green);

        $this->tempPath = $this->savePng($image);

        $blobs = (new ColorBlobDetector)->detectBlobsForColors($this->tempPath, ['#1ec8c8', '#288c28']);

        $this->assertCount(2, $blobs);
        $colors = array_column($blobs, 'color_hex');
        $this->assertContains('#1ec8c8', $colors);
        $this->assertContains('#288c28', $colors);
    }

    public function test_it_ignores_a_target_color_that_does_not_appear_in_the_image(): void
    {
        $image = $this->blankCanvas(100, 100);
        $this->tempPath = $this->savePng($image);

        $blobs = (new ColorBlobDetector)->detectBlobsForColors($this->tempPath, ['#ff00ff']);

        $this->assertCount(0, $blobs);
    }

    private function blankCanvas(int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        return $image;
    }

    private function savePng(GdImage $image): string
    {
        $path = tempnam(sys_get_temp_dir(), 'immersive-detector-').'.png';
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }
}
