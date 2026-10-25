<?php

namespace Omnidocs\Search;

use Omnidocs\LinkerInterface;
use Omnidocs\Model\Heading;
use Omnidocs\Model\Node;
use Omnidocs\Model\Page;
use Omnidocs\Model\Version;
use Omnidocs\OnePage;

/**
 * The search index of a static site: one JSON file, written with the site,
 * that the reader's browser downloads once and searches (assets/omnidocs.js):
 * the pages of each version given, a record per heading so that a hit lands
 * on its section, a record per API entry, and whatever the site adds of its
 * own (its guides, its news). Records keep the order they were added in, so
 * that two builds write the same file.
 *
 *   $index = (new SearchIndex())->version($manual->version(), $links, extra: 'flowjs 1.x');
 *   file_put_contents('search.json', $index->toJson('en'));
 */
final class SearchIndex
{
    /** @var list<Entry> */
    private array $entries = [];

    public function add(Entry ...$entries): self
    {
        array_push($this->entries, ...$entries);

        return $this;
    }

    /**
     * Every page of a version, its headings (levels 2 to $headingLevel) and its API entries.
     *
     * @param (callable(Node, Page): string)|null $title a page's title in the index (the page's own by default)
     */
    public function version(
        Version $version,
        LinkerInterface $links,
        string $kind = 'page',
        string $extra = '',
        bool $headings = true,
        bool $symbols = true,
        int $headingLevel = 3,
        int $excerpt = 160,
        ?callable $title = null,
    ): self {
        $titles = [];
        foreach ($version->pages() as $node) {
            $page = $version->page($node->path, $links);
            if (null === $page) {
                continue;
            }
            $name = null !== $title ? $title($node, $page) : $page->title;
            $titles[$node->path] = $name;
            $url = $links->page($node->path);
            $this->add(Entry::page($kind, $name, $url, $page->excerpt($excerpt), $page->headings(), $extra, $headingLevel));
            if ($headings) {
                foreach ($page->headings() as $heading) {
                    if ($heading->level > $headingLevel || '' === $heading->id) {
                        continue;
                    }
                    $this->add(new Entry('heading', $heading->text, $links->page($node->path, $heading->id), self::after($page->text(), $heading), extra: $extra, page: $name));
                }
            }
        }
        if ($symbols) {
            foreach ($version->symbols() as $symbol) {
                $this->add(new Entry(
                    'symbol',
                    $symbol->name,
                    $links->page($symbol->page, $symbol->anchor),
                    $symbol->summary,
                    extra: trim($symbol->kind.' '.$extra),
                    page: $titles[$symbol->page] ?? '',
                    signature: (string) $symbol->signature,
                ));
            }
        }

        return $this;
    }

    /**
     * A manual read as one page (OnePage): a record for the page, one per part (the pages it is made
     * of), one per heading and one per API entry, each leading to its anchor in the one page.
     */
    public function onePage(OnePage $one, string $title, string $kind = 'page', string $extra = '', bool $headings = true, bool $symbols = true, int $headingLevel = 3, int $excerpt = 160): self
    {
        $parts = $one->parts();
        $home = $parts[0] ?? null;
        $this->add(Entry::page($kind, $title, $one->url, null !== $home ? self::cut($home->text, $excerpt) : '', array_values(array_filter($one->headings(), static fn (Heading $h): bool => 2 === $h->level)), $extra, 2));
        $titles = [];
        foreach ($parts as $part) {
            $titles[$part->path] = $part->isHome() ? $title : $part->title;
            if (!$part->isHome()) {
                $this->add(new Entry('section', $part->title, $one->urlOf($part->path), self::cut($part->text, $excerpt), extra: $extra, page: $title));
            }
            if (!$headings) {
                continue;
            }
            foreach ($part->headings as $heading) {
                if ($heading->level > $headingLevel || '' === $heading->id || $heading->id === $part->anchor) {
                    continue;
                }
                $this->add(new Entry('heading', $heading->text, $one->url.'#'.$heading->id, self::after($part->text, $heading), extra: $extra, page: $titles[$part->path]));
            }
        }
        if ($symbols) {
            foreach ($one->version->symbols() as $symbol) {
                $this->add(new Entry('symbol', $symbol->name, $one->urlOf($symbol->page, $symbol->anchor), $symbol->summary, extra: trim($symbol->kind.' '.$extra), page: $titles[$symbol->page] ?? $title, signature: (string) $symbol->signature));
            }
        }

        return $this;
    }

    private static function cut(string $text, int $length): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return mb_strlen($text) > $length ? rtrim(mb_substr($text, 0, $length - 1)).'…' : $text;
    }

    /** @return list<Entry> */
    public function entries(): array
    {
        return $this->entries;
    }

    /** @return array{locale?: string, entries: list<array<string, string>>} */
    public function toArray(?string $locale = null): array
    {
        $entries = array_map(static fn (Entry $e): array => $e->toArray(), $this->entries);

        return null !== $locale ? ['locale' => $locale, 'entries' => $entries] : ['entries' => $entries];
    }

    public function toJson(?string $locale = null): string
    {
        return json_encode($this->toArray($locale), \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
    }

    /** What a page says after a heading: a hit on a section shows more than its title again. */
    private static function after(string $text, Heading $heading, int $length = 140): string
    {
        $at = mb_strpos($text, $heading->text);
        if (false === $at) {
            return '';
        }
        $rest = trim((string) preg_replace('/\s+/u', ' ', mb_substr($text, $at + mb_strlen($heading->text), $length * 2)));

        return mb_strlen($rest) > $length ? rtrim(mb_substr($rest, 0, $length - 1)).'…' : $rest;
    }
}
