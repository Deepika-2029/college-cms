<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ImageOptimizerService
{
    /**
     * Process an image to generate WebP variants.
     *
     * @param string $tmpPath The absolute path to the uploaded temporary file
     * @param string $basename The desired base filename without extension (e.g. '1744811_photo')
     * @param string $ext The original file extension
     * @return array Associative array of generated variants and their relative paths
     */
    public function process(string $tmpPath, string $basename, string $ext): array
    {
        if (!extension_loaded('gd')) {
            Log::warning('GD extension not loaded. Skipping image optimization.');
            return [];
        }

        $canWebp = function_exists('imagewebp');

        $variants = [];
        $mediaDir = public_path('media');
        
        if (!is_dir($mediaDir)) {
            @mkdir($mediaDir, 0755, true);
        }

        // Load the original image
        $image = $this->loadImage($tmpPath, $ext);
        
        if (!$image) {
            Log::warning("Failed to load image for optimization: {$tmpPath}");
            return [];
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Quality settings
        $targets = [
            'thumb'    => ['width' => 400,  'quality' => 80],
            'medium'   => ['width' => 800,  'quality' => 82],
            'large'    => ['width' => 1600, 'quality' => 85],
            'original' => ['width' => $width, 'quality' => 85], // keep original size, just convert to WebP
        ];

        $outExt = $canWebp ? 'webp' : (strtolower($ext) === 'png' ? 'png' : 'jpg');

        foreach ($targets as $size => $config) {
            $targetWidth = $config['width'];
            $quality = $config['quality'];
            
            // Only scale down, never scale up
            if ($targetWidth > $width && $size !== 'original') {
                continue;
            }

            if ($size === 'original') {
                $filename = "{$basename}.{$outExt}";
                $filePath = $mediaDir . '/' . $filename;
                
                // Convert to output format directly
                if ($this->saveOptimized($image, $filePath, $quality, $outExt)) {
                    $variants[$size] = '/media/' . $filename;
                }
            } else {
                $filename = "{$size}_{$basename}.{$outExt}";
                $filePath = $mediaDir . '/' . $filename;
                
                // Scale and convert
                $targetHeight = (int) round(($height / $width) * $targetWidth);
                $resized = imagescale($image, $targetWidth, $targetHeight, IMG_BICUBIC);
                
                if ($resized !== false) {
                    if ($this->saveOptimized($resized, $filePath, $quality, $outExt)) {
                        $variants[$size] = '/media/' . $filename;
                    }
                    imagedestroy($resized);
                }
            }
        }

        imagedestroy($image);

        return $variants;
    }

    /**
     * Load an image resource using GD based on its extension.
     */
    private function loadImage(string $path, string $ext)
    {
        switch (strtolower($ext)) {
            case 'jpg':
            case 'jpeg':
                return @imagecreatefromjpeg($path);
            case 'png':
                $img = @imagecreatefrompng($path);
                if ($img) {
                    imagepalettetotruecolor($img);
                    imagealphablending($img, true);
                    imagesavealpha($img, true);
                }
                return $img;
            case 'webp':
                return @imagecreatefromwebp($path);
            default:
                return null;
        }
    }

    /**
     * Save a GD image resource handling formats and transparency.
     */
    private function saveOptimized($image, string $path, int $quality, string $outExt): bool
    {
        if ($outExt === 'webp' && function_exists('imagewebp')) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            return @imagewebp($image, $path, $quality);
        }

        if ($outExt === 'png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            // PNG quality is 0-9. Map 80-100 to roughly 2-0 (lower is less compression = larger)
            $pngQuality = 9 - round(($quality / 100) * 9);
            return @imagepng($image, $path, max(0, min(9, (int)$pngQuality)));
        }

        // Default to JPG
        // Fill transparent bk with white for JPG if necessary
        $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
        $white = imagecolorallocate($bg, 255, 255, 255);
        imagefilledrectangle($bg, 0, 0, imagesx($image), imagesy($image), $white);
        imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        
        $result = @imagejpeg($bg, $path, $quality);
        imagedestroy($bg);
        return $result;
    }
}
