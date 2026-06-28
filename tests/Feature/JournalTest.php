<?php

namespace Tests\Feature;

use App\Models\JournalPost;
use App\Models\Media;
use App\Models\Tag;
use App\Models\User;
use App\Services\Media\MediaUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JournalTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // ── Public: index ───────────────────────────────────────────────────────

    public function test_journal_index_shows_only_published_posts(): void
    {
        JournalPost::factory()->published()->create(['title' => 'Published Post']);
        JournalPost::factory()->create(['title' => 'Draft Post', 'status' => 'draft']);

        $response = $this->get(route('journal.index'));

        $response->assertOk();
        $response->assertSee('Published Post');
        $response->assertDontSee('Draft Post');
    }

    public function test_journal_index_shows_empty_state_when_no_posts(): void
    {
        $response = $this->get(route('journal.index'));

        $response->assertOk();
        $response->assertSee('No posts yet');
    }

    // ── Public: show ────────────────────────────────────────────────────────

    public function test_journal_show_renders_published_post(): void
    {
        $post = JournalPost::factory()->published()->create(['title' => 'My Great Post']);

        $response = $this->get(route('journal.show', $post->slug));

        $response->assertOk();
        $response->assertSee('My Great Post');
    }

    public function test_journal_show_returns_404_for_draft(): void
    {
        $post = JournalPost::factory()->create(['status' => 'draft']);

        $this->get(route('journal.show', $post->slug))->assertNotFound();
    }

    public function test_journal_show_returns_404_for_missing_slug(): void
    {
        $this->get(route('journal.show', 'nonexistent-slug'))->assertNotFound();
    }

    // ── Public: RSS feed ────────────────────────────────────────────────────

    public function test_rss_feed_returns_xml_with_published_posts(): void
    {
        JournalPost::factory()->published()->create(['title' => 'Feed Post']);
        JournalPost::factory()->create(['status' => 'draft', 'title' => 'Draft Post']);

        $response = $this->get(route('journal.feed'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
        $response->assertSee('Feed Post');
        $response->assertDontSee('Draft Post');
    }

    // ── Admin: create / store ───────────────────────────────────────────────

    public function test_admin_can_view_create_form_with_tags(): void
    {
        Tag::factory()->create(['name' => 'Wood Crafts']);

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.journal.create'));

        $response->assertOk();
        $response->assertSee('Wood Crafts');
    }

    public function test_admin_can_create_published_post_with_tags(): void
    {
        $tag = Tag::factory()->create();

        $this->actingAs($this->adminUser())
            ->post(route('admin.journal.store'), [
                'title' => 'Test Journal Post',
                'slug' => 'test-journal-post',
                'body' => '<p>Body content.</p>',
                'status' => 'published',
                'tags' => [$tag->id],
            ])
            ->assertRedirect(route('admin.journal.index'))
            ->assertSessionHas('success');

        $post = JournalPost::where('slug', 'test-journal-post')->firstOrFail();
        $this->assertSame('published', $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->tags->contains($tag));
    }

    public function test_admin_store_auto_sets_published_at_when_blank(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.journal.store'), [
                'title' => 'Auto Date Post',
                'slug' => 'auto-date-post',
                'body' => 'Body.',
                'status' => 'published',
                'published_at' => '',
            ]);

        $post = JournalPost::where('slug', 'auto-date-post')->firstOrFail();
        $this->assertNotNull($post->published_at);
    }

    public function test_admin_can_create_draft_post(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.journal.store'), [
                'title' => 'Draft Post',
                'slug' => 'draft-post',
                'body' => 'Body.',
                'status' => 'draft',
            ])
            ->assertRedirect(route('admin.journal.index'));

        $post = JournalPost::where('slug', 'draft-post')->firstOrFail();
        $this->assertSame('draft', $post->status);
        $this->assertNull($post->published_at);
    }

    public function test_admin_store_rejects_invalid_status(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.journal.store'), [
                'title' => 'Post',
                'slug' => 'post',
                'body' => 'Body.',
                'status' => 'invalid-status',
            ])
            ->assertSessionHasErrors(['status']);
    }

    // ── Admin: edit / update ────────────────────────────────────────────────

    public function test_admin_can_view_edit_form_with_tags(): void
    {
        $post = JournalPost::factory()->create();
        Tag::factory()->create(['name' => 'Earrings']);

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.journal.edit', $post));

        $response->assertOk();
        $response->assertSee('Earrings');
    }

    public function test_admin_can_update_post_status_and_tags(): void
    {
        $post = JournalPost::factory()->create(['status' => 'draft']);
        $tag = Tag::factory()->create();

        $this->actingAs($this->adminUser())
            ->put(route('admin.journal.update', $post), [
                'title' => $post->title,
                'slug' => $post->slug,
                'body' => $post->body,
                'status' => 'published',
                'tags' => [$tag->id],
            ])
            ->assertRedirect(route('admin.journal.index'))
            ->assertSessionHas('success');

        $post->refresh();
        $this->assertSame('published', $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->tags->contains($tag));
    }

    public function test_admin_update_syncs_tags_removing_old_ones(): void
    {
        $post = JournalPost::factory()->create();
        $oldTag = Tag::factory()->create();
        $newTag = Tag::factory()->create();
        $post->tags()->sync([$oldTag->id]);

        $this->actingAs($this->adminUser())
            ->put(route('admin.journal.update', $post), [
                'title' => $post->title,
                'slug' => $post->slug,
                'body' => $post->body,
                'status' => 'draft',
                'tags' => [$newTag->id],
            ]);

        $post->refresh()->load('tags');
        $this->assertFalse($post->tags->contains($oldTag));
        $this->assertTrue($post->tags->contains($newTag));
    }

    // ── Admin: featured image (shared picker) ───────────────────────────────

    public function test_admin_can_attach_featured_image_from_library(): void
    {
        $media = Media::factory()->create();

        $this->actingAs($this->adminUser())
            ->post(route('admin.journal.store'), [
                'title' => 'Post With Image',
                'slug' => 'post-with-image',
                'body' => 'Body.',
                'status' => 'draft',
                'featured_image_id' => $media->id,
            ])
            ->assertRedirect(route('admin.journal.index'));

        $post = JournalPost::where('slug', 'post-with-image')->firstOrFail();
        $this->assertSame($media->id, $post->featured_image_id);
    }

    public function test_admin_can_upload_a_featured_image_file_that_gets_a_webp_variant(): void
    {
        $disk = config('filesystems.default');
        Storage::fake($disk);

        $this->actingAs($this->adminUser())
            ->post(route('admin.journal.store'), [
                'title' => 'Uploaded Image Post',
                'slug' => 'uploaded-image-post',
                'body' => 'Body.',
                'status' => 'draft',
                'featured_image' => UploadedFile::fake()->image('feat.png', 64, 64),
            ])
            ->assertRedirect();

        $post = JournalPost::where('slug', 'uploaded-image-post')->firstOrFail();
        $this->assertNotNull($post->featured_image_id);

        $media = Media::findOrFail($post->featured_image_id);
        Storage::disk($disk)->assertExists($media->path);
        Storage::disk($disk)->assertExists(MediaUploader::webpPath($media->path));
    }

    public function test_admin_can_remove_a_featured_image_on_update(): void
    {
        $media = Media::factory()->create();
        $post = JournalPost::factory()->create(['featured_image_id' => $media->id]);

        $this->actingAs($this->adminUser())
            ->put(route('admin.journal.update', $post), [
                'title' => $post->title,
                'slug' => $post->slug,
                'body' => $post->body,
                'status' => 'draft',
                'featured_image_id' => '',
            ]);

        $this->assertNull($post->refresh()->featured_image_id);
    }

    // ── Admin: destroy ──────────────────────────────────────────────────────

    public function test_admin_can_delete_post(): void
    {
        $post = JournalPost::factory()->create();

        $this->actingAs($this->adminUser())
            ->delete(route('admin.journal.destroy', $post))
            ->assertRedirect(route('admin.journal.index'));

        $this->assertModelMissing($post);
    }

    // ── Auth guards ─────────────────────────────────────────────────────────

    public function test_guest_cannot_access_admin_journal(): void
    {
        $this->get(route('admin.journal.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_admin_journal(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)
            ->get(route('admin.journal.index'))
            ->assertForbidden();
    }
}
