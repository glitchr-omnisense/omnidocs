<?php

namespace Omnidocs\Model;

/**
 * A heading of a page: its level (1 to 6), the id it carries in the page's
 * HTML - the anchor "on this page" and the search link to - and its text.
 */
final class Heading
{
    public function __construct(
        public readonly int $level,
        public readonly string $id,
        public readonly string $text,
    ) {
    }

    /** @return array{level: int, id: string, text: string} */
    public function toArray(): array
    {
        return ['level' => $this->level, 'id' => $this->id, 'text' => $this->text];
    }
}
