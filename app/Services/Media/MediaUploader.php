<?php

namespace App\Services\Media;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class MediaUploader
{
    /**
     * MIME types we accept for upload, mapped to a canonical extension.
     *
     * @var array<string, string>
     */
    private const EXTENSION_MAP = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    public function __construct(private ImageManager $imageManager) {}

    /**
     * Store an uploaded file on the configured media disk, generate a WebP
     * variant for raster JPEG/PNG sources, and persist a Media record.
     */
    public function store(UploadedFile $file, ?string $altText = null): Media
    {
        $disk = $this->disk();
        $original = $file->getClientOriginalName();
        $extension = self::EXTENSION_MAP[$file->getMimeType()] ?? null;

        if (! $extension) {
            throw new \InvalidArgumentException('Unsupported file type.');
        }

        $filename = Str::slug(pathinfo($original, PATHINFO_FILENAME)).'-'.uniqid().'.'.$extension;
        $path = $file->storeAs('media', $filename, $disk);

        if ($path === false) {
            $reason = $this->diagnoseWriteFailure($disk, "media/{$filename}", (string) file_get_contents($file->getRealPath()));
            throw new \RuntimeException("Failed to store file on {$disk}: {$reason}");
        }

        $this->generateWebpVariant($disk, $path, file_get_contents($file->getRealPath()));

        return Media::create([
            'filename' => $filename,
            'original_name' => $original,
            'path' => $path,
            'disk' => $disk,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'alt_text' => $altText,
            'uploaded_by' => auth()->id(),
        ]);
    }

    /**
     * Generate (or regenerate) the WebP sibling for an image already stored on
     * the given disk at $path. No-op for non-JPEG/PNG sources. Returns the WebP
     * path on success, null when skipped or on failure.
     */
    public function generateWebpVariant(string $disk, string $path, ?string $contents = null): ?string
    {
        $webpPath = self::webpPath($path);

        if ($webpPath === null) {
            return null;
        }

        try {
            $contents ??= Storage::disk($disk)->get($path);
            $encoded = (string) $this->imageManager->decode($contents)->encode(new WebpEncoder(quality: 80));

            if (Storage::disk($disk)->put($webpPath, $encoded) === false) {
                throw new \RuntimeException("Storage write failed for {$webpPath}: {$this->diagnoseWriteFailure($disk, $webpPath, $encoded)}");
            }

            return $webpPath;
        } catch (\Throwable $e) {
            Log::warning('WebP variant generation failed', [
                'disk' => $disk,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Delete a Media record's stored file along with any WebP sibling.
     */
    public function deleteWithVariants(Media $media): void
    {
        Storage::disk($media->disk)->delete($media->path);

        if ($webpPath = self::webpPath($media->path)) {
            Storage::disk($media->disk)->delete($webpPath);
        }
    }

    /**
     * Derive the WebP sibling path for a JPEG/PNG source, mirroring the
     * frontend's `<picture>` derivation. Returns null for other types.
     */
    public static function webpPath(string $path): ?string
    {
        if (! preg_match('/\.(png|jpe?g)$/i', $path)) {
            return null;
        }

        return preg_replace('/\.(png|jpe?g)$/i', '.webp', $path);
    }

    private function disk(): string
    {
        return config('filesystems.default');
    }

    /**
     * The configured disk (e.g. R2) has `'throw' => false`, so a failed put()
     * returns a silent `false` instead of surfacing the underlying S3 error.
     * Retry once through a throwing instance of the same disk purely to
     * capture a diagnostic message for the log — production behaviour for
     * callers is unchanged, since the outer catch still returns null either way.
     */
    private function diagnoseWriteFailure(string $disk, string $path, string $contents): string
    {
        try {
            Storage::build(array_merge(config("filesystems.disks.{$disk}", []), ['throw' => true]))
                ->put($path, $contents);

            return 'no exception on throwing retry — transient failure?';
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
