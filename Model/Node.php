<?php

namespace Omnidocs\Model;

/**
 * A place in a manual's contents: a page, or a section that holds pages (a
 * folder of Markdown files, a Sphinx toctree caption, a group of API
 * entries). Its path is its address inside the manual ("" for the home,
 * "sticky", "guides/install"); a page knows the file it is read from,
 * relative to the repository ("docs/sticky.md"), a section has none.
 */
final class Node
{
    /**
     * @param list<Node> $children
     */
    public function __construct(
        public readonly string $path,
        public readonly string $title,
        public readonly ?string $source = null,
        public readonly int $order = 100,
        public readonly array $children = [],
        private readonly bool $section = false,
    ) {
    }

    /**
     * A section: a title over pages, not a page of its own.
     *
     * @param list<Node> $children
     */
    public static function section(string $path, string $title, array $children, int $order = 100): self
    {
        return new self($path, $title, null, $order, $children, true);
    }

    public function isSection(): bool
    {
        return $this->section;
    }

    /** Whether this node, or a node under it, is the one at $path. */
    public function contains(string $path): bool
    {
        if ($this->path === $path) {
            return true;
        }
        foreach ($this->children as $child) {
            if ($child->contains($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every page of a tree, depth first, in reading order (sections left out).
     *
     * @param list<Node> $tree
     *
     * @return list<Node>
     */
    public static function flatten(array $tree): array
    {
        $flat = [];
        foreach ($tree as $node) {
            if (!$node->isSection()) {
                $flat[] = $node;
            }
            array_push($flat, ...self::flatten($node->children));
        }

        return $flat;
    }
}
