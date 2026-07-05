<?php

namespace App\Support;

/**
 * Extracts question/answer pairs from a journal post's rendered HTML body so
 * the page can emit FAQPage JSON-LD.
 *
 * Journal bodies are CommonMark-converted markdown (see BlogDraftParser), which
 * produces a flat list of top-level elements. Our pillar posts mark their FAQ
 * with an `<h2>Frequently Asked Questions</h2>` heading, each question as an
 * `<h3>`, and the answer as the following sibling(s) up to the next heading.
 * The FAQ block ends at the next `<h2>` (or end of document).
 */
class FaqExtractor
{
    /**
     * @return array<int, array{question: string, answer: string}>
     */
    public static function fromHtml(?string $html): array
    {
        $html = trim((string) $html);

        if ($html === '') {
            return [];
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        // Wrap so we have a single container to walk; force UTF-8 and suppress
        // the implicit <html>/<body> and doctype the parser would otherwise add.
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="faq-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('faq-root');

        if ($root === null) {
            return [];
        }

        $faqs = [];
        $inFaq = false;
        $question = null;
        $answerParts = [];

        $flush = static function () use (&$faqs, &$question, &$answerParts) {
            if ($question !== null) {
                $answer = self::normalize(implode(' ', $answerParts));
                if ($answer !== '') {
                    $faqs[] = ['question' => $question, 'answer' => $answer];
                }
            }
            $question = null;
            $answerParts = [];
        };

        foreach ($root->childNodes as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($node->nodeName);

            if ($tag === 'h2') {
                if ($inFaq) {
                    // A second <h2> closes the FAQ block.
                    $flush();
                    break;
                }
                if (self::isFaqHeading(self::normalize($node->textContent))) {
                    $inFaq = true;
                }

                continue;
            }

            if (! $inFaq) {
                continue;
            }

            if ($tag === 'h3') {
                $flush();
                $question = self::normalize($node->textContent);

                continue;
            }

            if ($question !== null) {
                $answerParts[] = $node->textContent;
            }
        }

        // Reached end of document while still inside the FAQ block.
        $flush();

        return $faqs;
    }

    private static function isFaqHeading(string $text): bool
    {
        $text = strtolower($text);

        return str_contains($text, 'frequently asked questions') || $text === 'faq' || $text === 'faqs';
    }

    private static function normalize(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }
}
