<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_care_guide_slug_is_whitelisted_and_renders_its_page(): void
    {
        Page::create([
            'title' => 'Materials, Sizing and Care',
            'slug' => 'care-guide',
            'body' => 'Coming soon.',
        ]);

        $response = $this->get('/care-guide');

        $response->assertOk();
        $response->assertSee('Materials, Sizing and Care');
    }

    #[Test]
    public function an_unwhitelisted_slug_is_not_served_as_a_page(): void
    {
        Page::create([
            'title' => 'Secret',
            'slug' => 'not-whitelisted',
            'body' => 'Nope.',
        ]);

        $this->get('/not-whitelisted')->assertNotFound();
    }

    #[Test]
    public function a_title_with_an_ampersand_is_html_escaped_exactly_once(): void
    {
        Page::create([
            'title' => 'Terms & Conditions',
            'slug' => 'terms-and-conditions',
            'body' => 'Body.',
        ]);

        $response = $this->get('/terms-and-conditions');

        $response->assertOk();
        // Escaped once for HTML…
        $response->assertSee('<title>Terms &amp; Conditions | Timber Trace Crafts</title>', false);
        // …and never double-encoded.
        $response->assertDontSee('&amp;amp;', false);
    }

    #[Test]
    public function the_meta_description_is_html_escaped_exactly_once(): void
    {
        Page::create([
            'title' => 'Shipping Policy',
            'slug' => 'shipping-policy',
            'body' => 'Salt & Pepper care and handling details.',
        ]);

        $response = $this->get('/shipping-policy');

        $response->assertOk();
        // meta description is derived from the body; the '&' escapes once…
        $response->assertSee('content="Salt &amp; Pepper care and handling details.', false);
        // …and is never double-encoded.
        $response->assertDontSee('&amp;amp;', false);
    }
}
