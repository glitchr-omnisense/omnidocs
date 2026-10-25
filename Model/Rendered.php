<?php

namespace Omnidocs\Model;

/**
 * A text once rendered to HTML: the HTML (without its first-level title when
 * the format gives one, which the page prints itself), that title, the
 * headings for "on this page", the metadata the source carried (a Markdown
 * front matter), and its plain text for a search index and a description.
 */
final class Rendered
{
    /**
     * @param list<Heading>        $headings
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly string $html,
        public readonly ?string $title = null,
        public readonly array $headings = [],
        public readonly array $meta = [],
        public readonly string $text = '',
    ) {
    }

    /** The plain text, its spaces collapsed, cut at $length characters with an ellipsis. */
    public function excerpt(int $length = 220): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $this->text));

        return mb_strlen($text) > $length ? rtrim(mb_substr($text, 0, $length - 1)).'…' : $text;
    }
}
