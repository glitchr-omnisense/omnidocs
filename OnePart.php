<?php

namespace Omnidocs;

use Omnidocs\Model\Heading;
use Omnidocs\Model\Page;

/** A page of a manual as a part of its one page (OnePage): where it starts, its HTML there, the file to edit. */
final class OnePart
{
    /**
     * @param list<Heading> $headings its title first (but for the home), then its own, one level down
     */
    public function __construct(
        public readonly string $path,
        public readonly string $title,
        /** Its anchor in the one page; "" for the home. */
        public readonly string $anchor,
        public readonly string $html,
        public readonly array $headings,
        /** The file it is read from, relative to the repository: "edit this page". */
        public readonly ?string $source,
        public readonly string $text,
        /** The page as its source rendered it, on its own. */
        public readonly Page $page,
    ) {
    }

    public function isHome(): bool
    {
        return '' === $this->path;
    }
}
