<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RobotsTxtTest extends TestCase
{
    /** The six transactional paths that must never be crawlable. */
    private const TRANSACTIONAL = ['/admin', '/cart', '/checkout', '/login', '/register', '/account'];

    /** Named AI-bot groups that must each repeat the transactional Disallows. */
    private const AI_GROUPS = [
        'GPTBot',
        'ClaudeBot',
        'PerplexityBot',
        'Perplexity-User',
        'Applebot-Extended',
        'Meta-ExternalAgent',
        'cohere-ai',
        'Google-Extended',
    ];

    private function servedBody(): string
    {
        $response = $this->get('/robots.txt');
        $response->assertOk();

        return $response->getContent();
    }

    /**
     * Parse robots.txt into groups keyed by user-agent token. Each group holds
     * the raw rule lines (Allow/Disallow/…) that apply to that agent. Rules are
     * NOT inherited from other groups, so this reflects real crawler semantics.
     *
     * @return array<string, array<int, string>>
     */
    private function parseGroups(string $body): array
    {
        $groups = [];
        /** @var array<int, string> $currentAgents */
        $currentAgents = [];
        $sawRuleForCurrent = false;

        foreach (preg_split('/\R/', $body) as $rawLine) {
            $line = trim($rawLine);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^User-agent:\s*(.+)$/i', $line, $m)) {
                // A User-agent line after we have already collected rules starts
                // a fresh group; consecutive User-agent lines share one group.
                if ($sawRuleForCurrent) {
                    $currentAgents = [];
                    $sawRuleForCurrent = false;
                }
                $agent = trim($m[1]);
                $currentAgents[] = $agent;
                $groups[$agent] ??= [];

                continue;
            }

            // A rule line applies to every agent currently in scope.
            foreach ($currentAgents as $agent) {
                $groups[$agent][] = $line;
            }
            if ($currentAgents !== []) {
                $sawRuleForCurrent = true;
            }
        }

        return $groups;
    }

    #[Test]
    public function robots_route_returns_200_text_plain_matching_the_static_file(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));

        $this->assertSame(
            file_get_contents(public_path('robots.txt')),
            $response->getContent(),
            'Served robots.txt must be byte-identical to public/robots.txt (single source of truth).'
        );
    }

    #[Test]
    public function google_extended_group_allows_all(): void
    {
        $groups = $this->parseGroups($this->servedBody());

        $this->assertArrayHasKey('Google-Extended', $groups);
        $this->assertContains('Allow: /', $groups['Google-Extended']);
    }

    #[Test]
    public function each_named_ai_group_repeats_all_six_transactional_disallows(): void
    {
        $groups = $this->parseGroups($this->servedBody());

        foreach (self::AI_GROUPS as $agent) {
            $this->assertArrayHasKey($agent, $groups, "Missing named group for {$agent}");

            foreach (self::TRANSACTIONAL as $path) {
                $this->assertContains(
                    "Disallow: {$path}",
                    $groups[$agent],
                    "Group {$agent} must itself Disallow {$path} (no inheritance from *)."
                );
            }
        }
    }

    #[Test]
    public function star_group_and_training_crawlers_and_sitemap_are_preserved(): void
    {
        $body = $this->servedBody();
        $groups = $this->parseGroups($body);

        // User-agent: * keeps the six transactional Disallows.
        $this->assertArrayHasKey('*', $groups);
        foreach (self::TRANSACTIONAL as $path) {
            $this->assertContains("Disallow: {$path}", $groups['*']);
        }

        // Training-data crawlers fully blocked.
        $this->assertArrayHasKey('CCBot', $groups);
        $this->assertContains('Disallow: /', $groups['CCBot']);
        $this->assertArrayHasKey('Bytespider', $groups);
        $this->assertContains('Disallow: /', $groups['Bytespider']);

        // Sitemap line intact.
        $this->assertStringContainsString('Sitemap: https://timbertracecrafts.com/sitemap.xml', $body);
    }
}
