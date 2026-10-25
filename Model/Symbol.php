<?php

namespace Omnidocs\Model;

/**
 * An entry of an API reference: a class, a function, a method, an option, a
 * member... as the format names it. Its name is the one a reader searches
 * and a cross-reference gives ("StickyStops.attach", "requests.get",
 * "tinyxml2::XMLDocument::Parse"); it lives on a page of the manual, at an
 * anchor of that page.
 */
final class Symbol
{
    public function __construct(
        /** What it is, in the format's words: "class", "function", "method", "member", "option", "py:function"... */
        public readonly string $kind,
        public readonly string $name,
        /** The page that documents it (its path in the manual). */
        public readonly string $page,
        /** Its id in that page's HTML; null: the page itself. */
        public readonly ?string $anchor = null,
        /** Its first sentence or so, as plain text. */
        public readonly string $summary = '',
        /** How it is called or declared: "attach(scroller, options)". */
        public readonly ?string $signature = null,
        /** The entry it belongs to ("StickyStops" for "StickyStops.attach"). */
        public readonly ?string $parent = null,
        /** Where it is defined, relative to the repository: "src/js/stops.js#L1213". */
        public readonly ?string $source = null,
    ) {
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return ['kind' => $this->kind, 'name' => $this->name, 'page' => $this->page] + array_filter([
            'anchor' => $this->anchor,
            'summary' => $this->summary,
            'signature' => $this->signature,
            'parent' => $this->parent,
            'source' => $this->source,
        ], static fn (?string $value): bool => null !== $value && '' !== $value);
    }
}
