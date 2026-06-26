<?php

namespace App\Services;

use League\CommonMark\CommonMarkConverter;

class BlogDraftParser
{
    /**
     * Parse a draft file (.md or .html) into structured fields.
     *
     * @return array{title:string,slug:string,excerpt:?string,body:string,meta_title:?string,meta_description:?string,status:string,published_at:?string,tags:array<string>}
     */
    public function parse(string $path): array
    {
        $raw = file_get_contents($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        [$frontmatter, $body] = $this->splitFrontmatter($raw);
        $fields = $this->parseFrontmatter($frontmatter);

        $body = $this->stripHtmlComments($body);

        $fields['body'] = $extension === 'md'
            ? $this->convertMarkdownToHtml($body)
            : $body;

        return $fields;
    }

    /**
     * @return array{array<string,mixed>, string}
     */
    private function splitFrontmatter(string $raw): array
    {
        if (! str_starts_with(ltrim($raw), '---')) {
            return [[], $raw];
        }

        $parts = preg_split('/^---\s*$/m', ltrim($raw), 3);

        if (count($parts) < 3) {
            return [[], $raw];
        }

        return [$parts[1], $parts[2]];
    }

    /**
     * @return array<string,mixed>
     */
    private function parseFrontmatter(string $yaml): array
    {
        $fields = [
            'title' => '',
            'slug' => '',
            'excerpt' => null,
            'meta_title' => null,
            'meta_description' => null,
            'status' => 'draft',
            'published_at' => null,
            'tags' => [],
        ];

        foreach (explode("\n", $yaml) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode(':', $line, 2));
            $value = trim($value, '"\'');

            match ($key) {
                'title' => $fields['title'] = $value,
                'slug' => $fields['slug'] = $value,
                'excerpt' => $fields['excerpt'] = $value ?: null,
                'meta_title' => $fields['meta_title'] = $value ?: null,
                'meta_description' => $fields['meta_description'] = $value ?: null,
                'status' => $fields['status'] = in_array($value, ['draft', 'published']) ? $value : 'draft',
                'published_at' => $fields['published_at'] = $value ?: null,
                'tags' => $fields['tags'] = $this->parseTagList($line),
                default => null,
            };
        }

        return $fields;
    }

    /** @return array<string> */
    private function parseTagList(string $line): array
    {
        if (preg_match('/\[(.+)\]/', $line, $m)) {
            return array_map('trim', explode(',', $m[1]));
        }

        return [];
    }

    private function stripHtmlComments(string $body): string
    {
        return preg_replace('/<!--.*?-->/s', '', $body);
    }

    private function convertMarkdownToHtml(string $markdown): string
    {
        return (new CommonMarkConverter)->convert(trim($markdown))->getContent();
    }
}
