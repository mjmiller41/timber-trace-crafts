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
}
