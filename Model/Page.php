<?php

namespace Omnidocs\Model;

/**
 * A page of a manual, ready for a template: its path in the manual, its
 * title, its contents (HTML, headings, metadata, plain text), the file it is
 * read from relative to the repository - what "edit this page" opens - and
 * the API entries it documents.
 */
final class Page
{
    /**
     * @param list<Symbol> $symbols
     */
    public function __construct(
        public readonly string $path,
        public readonly string $title,
        public readonly Rendered $content,
        public readonly ?string $source = null,
        public readonly array $symbols = [],
    ) {
    }

    public function html(): string
    {
        return $this->content->html;
    }

    /** @return list<Heading> */
    public function headings(): array
    {
        return $this->content->headings;
    }

    /** @return array<string, mixed> */
    public function meta(): array
    {
        return $this->content->meta;
    }

    public function text(): string
    {
        return $this->content->text;
    }

    public function excerpt(int $length = 220): string
    {
        return $this->content->excerpt($length);
    }
}
