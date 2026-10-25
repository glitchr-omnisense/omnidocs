<?php

namespace Omnidocs\Search;

use Omnidocs\Model\Heading;

/**
 * One record of a search index: what a hit shows and where it leads. Written
 * with short keys, the index being downloaded whole by the reader's browser:
 *
 *   k  its kind: "page", "heading", "symbol" - or a site's own ("guide", "news"...)
 *   t  its title (a page's title, a heading's text, a symbol's name)
 *   u  its address (with the anchor of a heading or a symbol)
 *   d  a short text: the page's first words, what follows a heading, a symbol's summary
 *   h  the page's headings, " · " between them
 *   x  words searched but not shown in the title: a symbol's kind, a manual's name and version
 *   p  the page a heading or a symbol is on
 *   s  a symbol's signature
 *
 * An empty field is left out.
 */
final class Entry
{
    /**
     * @param list<Heading>|list<string> $headings
     */
    public function __construct(
        public readonly string $kind,
        public readonly string $title,
        public readonly string $url,
        public readonly string $text = '',
        public readonly array $headings = [],
        public readonly string $extra = '',
        public readonly string $page = '',
        public readonly string $signature = '',
    ) {
    }

    /**
     * A page's entry, its headings down to $maxLevel listed.
     *
     * @param list<Heading> $headings
     */
    public static function page(string $kind, string $title, string $url, string $text, array $headings = [], string $extra = '', int $maxLevel = 3): self
    {
        return new self($kind, $title, $url, $text, array_values(array_filter($headings, static fn (Heading $h): bool => $h->level <= $maxLevel)), $extra);
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        $headings = array_map(static fn (Heading|string $h): string => $h instanceof Heading ? $h->text : $h, $this->headings);

        return array_filter([
            'k' => $this->kind,
            't' => $this->title,
            'u' => $this->url,
            'd' => $this->text,
            'h' => implode(' · ', $headings),
            'x' => $this->extra,
            'p' => $this->page,
            's' => $this->signature,
        ], static fn (string $v): bool => '' !== $v);
    }
}
