<?php

namespace Omnidocs\Model;

use Omnidocs\LinkerInterface;
use Omnidocs\SourceInterface;

/**
 * One version of a manual ("1.x", "4.x", "main"): its source, the branch it
 * follows and the commit it is read at, whether it has reached its end of
 * life. Reads its pages in reading order - the home first, then the tree
 * depth first - for the previous and next links.
 */
final class Version
{
    /** @var list<Node>|null */
    private ?array $pages = null;

    /** @var list<Symbol>|null */
    private ?array $symbols = null;

    /** @var array<string, Page|null> */
    private array $rendered = [];

    public function __construct(
        public readonly string $name,
        public readonly SourceInterface $source,
        public readonly ?string $branch = null,
        /** The commit (or tag) it is read at. */
        public readonly ?string $ref = null,
        public readonly bool $eol = false,
    ) {
    }

    public function home(): ?Node
    {
        return $this->source->home();
    }

    /** @return list<Node> */
    public function tree(): array
    {
        return $this->source->tree();
    }

    /** @return list<Node> every page, the home first, then the tree depth first */
    public function pages(): array
    {
        if (null === $this->pages) {
            $home = $this->source->home();
            $this->pages = [...(null !== $home ? [$home] : []), ...Node::flatten($this->source->tree())];
        }

        return $this->pages;
    }

    /** The node of a page, by its path. */
    public function node(string $path): ?Node
    {
        foreach ($this->pages() as $node) {
            if ($node->path === $path) {
                return $node;
            }
        }

        return null;
    }

    /** A page rendered, kept for the next call with the same linker. */
    public function page(string $path, ?LinkerInterface $links = null): ?Page
    {
        $key = $path.'@'.(null !== $links ? spl_object_id($links) : '');
        if (!\array_key_exists($key, $this->rendered)) {
            $this->rendered[$key] = null !== $this->node($path) ? $this->source->page($path, $links) : null;
        }

        return $this->rendered[$key];
    }

    public function previous(string $path): ?Node
    {
        $pages = $this->pages();
        foreach ($pages as $i => $node) {
            if ($node->path === $path) {
                return $pages[$i - 1] ?? null;
            }
        }

        return null;
    }

    public function next(string $path): ?Node
    {
        $pages = $this->pages();
        foreach ($pages as $i => $node) {
            if ($node->path === $path) {
                return $pages[$i + 1] ?? null;
            }
        }

        return null;
    }

    /** @return list<Symbol> */
    public function symbols(): array
    {
        return $this->symbols ??= $this->source->symbols();
    }

    /** An API entry by its name ("StickyStops.attach"). */
    public function symbol(string $name): ?Symbol
    {
        foreach ($this->symbols() as $symbol) {
            if ($symbol->name === $name) {
                return $symbol;
            }
        }

        return null;
    }
}
