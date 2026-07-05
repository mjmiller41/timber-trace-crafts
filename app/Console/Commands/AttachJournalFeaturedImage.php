<?php

namespace App\Console\Commands;

use App\Models\JournalPost;
use App\Services\Media\MediaUploader;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;

/**
 * Upload a local image to the media disk and attach it as a journal post's
 * featured image, without going through the (2FA-gated) admin UI.
 *
 * The alt text defaults to the `cover_image_alt` frontmatter of the matching
 * draft in `.claude/blog/posts/{slug}.md`, so it matches what the content
 * pipeline authored. Pass --alt to override.
 */
class AttachJournalFeaturedImage extends Command
{
    protected $signature = 'journal:attach-featured
        {slug : The journal post slug}
        {image : Path to a local image file (jpg/png/webp/gif)}
        {--alt= : Alt text; defaults to the draft cover_image_alt frontmatter}';

    protected $description = 'Upload an image and set it as a journal post featured image with alt text';

    public function handle(MediaUploader $uploader): int
    {
        $slug = $this->argument('slug');
        $imagePath = $this->argument('image');

        $post = JournalPost::where('slug', $slug)->first();
        if (! $post) {
            $this->error("No journal post found for slug '{$slug}'.");

            return self::FAILURE;
        }

        if (! is_file($imagePath)) {
            $this->error("Image file not found: {$imagePath}");

            return self::FAILURE;
        }

        $alt = $this->option('alt') ?: $this->altFromDraft($slug);
        if (! $alt) {
            $this->warn('No --alt given and no cover_image_alt found in the draft; storing empty alt text.');
        }

        // Test-mode UploadedFile: lets us reuse the HTTP MediaUploader from CLI.
        $file = new UploadedFile(
            $imagePath,
            basename($imagePath),
            mime_content_type($imagePath) ?: null,
            null,
            true
        );

        $media = $uploader->store($file, $alt ?: null);

        $post->featured_image_id = $media->id;
        $post->save();

        $this->info("Attached media #{$media->id} to '{$slug}'.");
        $this->line("  URL: {$media->url()}");
        $this->line('  Alt: '.($alt ?: '(none)'));

        return self::SUCCESS;
    }

    private function altFromDraft(string $slug): ?string
    {
        $path = base_path(".claude/blog/posts/{$slug}.md");
        if (! is_file($path)) {
            return null;
        }

        if (preg_match('/^cover_image_alt:\s*"?(.+?)"?\s*$/m', file_get_contents($path), $m)) {
            return trim($m[1]);
        }

        return null;
    }
}
