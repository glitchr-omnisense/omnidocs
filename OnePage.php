<?php

namespace Omnidocs;

use Omnidocs\Html\Headings;
use Omnidocs\Html\Text;
use Omnidocs\Model\Heading;
use Omnidocs\Model\Node;
use Omnidocs\Model\Page;
use Omnidocs\Model\Version;
use Omnidocs\Support\Path;

/**
 * A version of a manual read as one page: every page in reading order, one
 * after the other, so that a package's whole documentation is one address -
 * read straight away, searched with the browser's own find in page, its
 * table of contents in a sidebar.
 *
 *  - the home comes first, as it is; every other page is a part: a section
 *    whose title is a second-level heading (its anchor, "sticky", from its
 *    path), its own headings one level down (its h2 becomes an h3), its ids
 *    prefixed with the part's anchor ("keys" becomes "sticky-keys"), so that
 *    no two ids of the page meet;
 *  - a link from a page to another page of the manual goes to that part, in
 *    the page (#sticky, #sticky-keys); the linker given keeps the files of
 *    the repository and the pictures;
 *  - the former addresses, one per page, are listed with the part they
 *    became and the prefix their anchors took (redirects()), for a static
 *    redirection that carries the visitor's #fragment over.
 *
 *   $one = new OnePage($manual->version(), $links, '/docs/flowjs/1.x');
 *   $one->parts();     // the home, then each page: title, anchor, HTML, the file to edit
 *   $one->toc();       // the headings down to the third level, nested: the sidebar
 *
 * With $groups, the manual's top-level sections (a folder, a toctree caption, a group of API entries)
 * group its parts: the table of contents lists each section with its pages under it, and the page
 * puts the section's title before its first part (div.doc-group, its own anchor). A section of a
 * single page that bears its name is that page alone, not a group of one.
 */
final class OnePage
{
    private const MARK = '#~omnidocs-part~';

    /** @var list<OnePart>|null */
    private ?array $parts = null;

    /** @var array<string, string>|null page path => its anchor */
    private ?array $anchors = null;

    /** @var list<array{id: string, title: string, paths: list<string>}>|null */
    private ?array $groupList = null;

    public function __construct(
        public readonly Version $version,
        private readonly LinkerInterface $links,
        /** The page's own address: what search records and redirections lead to. */
        public readonly string $url,
        private readonly int $tocLevel = 3,
        /** The top-level sections group the parts: in the table of contents, and a title before each group. */
        private readonly bool $groups = false,
    ) {
    }

    /** The anchor a page of the manual, or one of its headings, has in the one page; "" for the home's top. */
    public function anchor(string $path, ?string $fragment = null): string
    {
        $part = $this->anchors()[$path] ?? null;
        if (null === $part) {
            return (string) $fragment;
        }
        if ('' === $part) {
            return (string) $fragment;
        }

        return null !== $fragment && '' !== $fragment ? $part.'-'.$fragment : $part;
    }

    /** The address of a page of the manual, or one of its headings, in the one page. */
    public function urlOf(string $path, ?string $fragment = null): string
    {
        $anchor = $this->anchor($path, $fragment);

        return $this->url.('' !== $anchor ? '#'.$anchor : '');
    }

    /** @return list<OnePart> the home first, then every page in reading order */
    public function parts(): array
    {
        if (null !== $this->parts) {
            return $this->parts;
        }
        $inner = new class($this->links, $this) implements LinkerInterface {
            public function __construct(private readonly LinkerInterface $outer, private readonly OnePage $one)
            {
            }

            public function page(string $path, ?string $fragment = null): string
            {
                // Marked, so that the part's own anchors, prefixed afterwards, are told from these.
                return '#~omnidocs-part~'.$this->one->anchor($path, $fragment);
            }

            public function file(string $path, ?string $fragment = null): ?string
            {
                return $this->outer->file($path, $fragment);
            }

            public function image(string $path): ?string
            {
                return $this->outer->image($path);
            }
        };
        $parts = [];
        $opened = [];
        foreach ($this->version->pages() as $node) {
            $page = $this->version->source->page($node->path, $inner);
            if (null === $page) {
                continue;
            }
            $part = $this->part($node, $page);
            $group = $this->groupOf($node->path);
            if (null !== $group && !isset($opened[$group['id']])) {
                // The group's title, before its first part.
                $opened[$group['id']] = true;
                $title = '<div class="doc-group" id="'.htmlspecialchars($group['id'], \ENT_QUOTES).'"><p class="doc-group-title">'.htmlspecialchars($group['title'], \ENT_QUOTES).'</p></div>';
                $part = new OnePart($part->path, $part->title, $part->anchor, $title."\n".$part->html, $part->headings, $part->source, $part->text, $part->page);
            }
            $parts[] = $part;
        }

        return $this->parts = $parts;
    }

    /**
     * The groups of the parts (with $groups): each top-level section, its anchor, its title, its pages'
     * paths - but a section of one page that bears its name.
     *
     * @return list<array{id: string, title: string, paths: list<string>}>
     */
    public function groups(): array
    {
        if (!$this->groups) {
            return [];
        }
        if (null !== $this->groupList) {
            return $this->groupList;
        }
        $taken = array_flip(array_values($this->anchors()));
        $groups = [];
        foreach ($this->version->tree() as $node) {
            if (!$node->isSection()) {
                continue;
            }
            $paths = array_map(static fn (Node $page): string => $page->path, Node::flatten($node->children));
            // A section of a single page named as the page is that page alone, not a group of one.
            if ([] === $paths || (1 === \count($paths) && 0 === strcasecmp($node->title, (string) ($this->version->node($paths[0])?->title ?? '')))) {
                continue;
            }
            $groups[] = ['id' => Headings::id('group-'.Path::slug('' !== $node->path ? $node->path : $node->title), $taken), 'title' => $node->title, 'paths' => $paths];
        }

        return $this->groupList = $groups;
    }

    /** @return array{id: string, title: string, paths: list<string>}|null the group a page is in */
    private function groupOf(string $path): ?array
    {
        foreach ($this->groups() as $group) {
            if (\in_array($path, $group['paths'], true)) {
                return $group;
            }
        }

        return null;
    }

    /** The parts' HTML, one after the other. */
    public function html(): string
    {
        return implode("\n", array_map(static fn (OnePart $part): string => $part->html, $this->parts()));
    }

    /** @return list<Heading> every heading of the page, the parts' titles at the second level */
    public function headings(): array
    {
        $all = [];
        foreach ($this->parts() as $part) {
            array_push($all, ...$part->headings);
        }

        return $all;
    }

    /**
     * The table of contents: the second-level headings, each with its third-level ones (down to $tocLevel).
     *
     * @return list<array{heading: Heading, children: list<Heading>}>
     */
    public function toc(): array
    {
        if ([] !== $this->groups()) {
            return $this->groupedToc();
        }
        $toc = [];
        foreach ($this->headings() as $heading) {
            if (2 === $heading->level) {
                $toc[] = ['heading' => $heading, 'children' => []];
            } elseif ($heading->level <= $this->tocLevel && [] !== $toc && $heading->level > 2) {
                $toc[\count($toc) - 1]['children'][] = $heading;
            }
        }

        return $toc;
    }

    /**
     * The table of contents by group: each group with its parts under it; a part outside every group
     * at the top, with its own sections.
     *
     * @return list<array{heading: Heading, children: list<Heading>, group?: bool}>
     */
    private function groupedToc(): array
    {
        $toc = [];
        $groupAt = [];
        foreach ($this->parts() as $part) {
            $group = $this->groupOf($part->path);
            if (null === $group) {
                foreach ($part->headings as $heading) {
                    if (2 === $heading->level) {
                        $toc[] = ['heading' => $heading, 'children' => []];
                    } elseif ($heading->level > 2 && $heading->level <= $this->tocLevel && [] !== $toc && !($toc[\count($toc) - 1]['group'] ?? false)) {
                        $toc[\count($toc) - 1]['children'][] = $heading;
                    }
                }
                continue;
            }
            if (!isset($groupAt[$group['id']])) {
                $groupAt[$group['id']] = \count($toc);
                $toc[] = ['heading' => new Heading(2, $group['id'], $group['title']), 'children' => [], 'group' => true];
            }
            $toc[$groupAt[$group['id']]]['children'][] = new Heading(3, $part->anchor, $part->title);
        }

        return $toc;
    }

    /** The page's text, for a description. */
    public function text(): string
    {
        return implode(' ', array_map(static fn (OnePart $part): string => $part->text, $this->parts()));
    }

    /**
     * The former address of each page but the home: its path => the part's anchor, and the prefix a #fragment
     * of that address takes in the one page.
     *
     * @return array<string, array{anchor: string, prefix: string}>
     */
    public function redirects(): array
    {
        $redirects = [];
        foreach ($this->anchors() as $path => $anchor) {
            if ('' !== $path) {
                $redirects[$path] = ['anchor' => $anchor, 'prefix' => $anchor.'-'];
            }
        }

        return $redirects;
    }

    /** @return array<string, string> */
    private function anchors(): array
    {
        if (null === $this->anchors) {
            $taken = [];
            $home = $this->version->home();
            // The home's own anchors stay as they are: a part's anchor never takes one of them.
            if (null !== $home) {
                foreach ($this->version->source->page('', null)?->headings() ?? [] as $heading) {
                    $taken[$heading->id] = true;
                }
            }
            $anchors = [];
            foreach ($this->version->pages() as $node) {
                $anchors[$node->path] = '' === $node->path ? '' : Headings::id(Path::slug($node->path), $taken);
            }
            $this->anchors = $anchors;
        }

        return $this->anchors;
    }

    private function part(Node $node, Page $page): OnePart
    {
        $anchor = $this->anchor($node->path);
        $html = $page->html();
        $headings = $page->headings();
        if ('' !== $node->path) {
            // One level down, every id prefixed, every anchor of the part's own moved with them.
            $html = (string) preg_replace_callback('#<(/?)h([1-5])\b#i', static fn (array $m): string => '<'.$m[1].'h'.((int) $m[2] + 1), $html);
            $html = (string) preg_replace_callback('#\sid="([^"]*)"#', static fn (array $m): string => ' id="'.$anchor.'-'.$m[1].'"', $html);
            $html = (string) preg_replace_callback('#\shref="\#(?!~omnidocs-part~)([^"]*)"#', static fn (array $m): string => '' === $m[1] ? $m[0] : ' href="#'.$anchor.'-'.$m[1].'"', $html);
            $headings = array_map(static fn (Heading $h): Heading => new Heading(min(6, $h->level + 1), $anchor.'-'.$h->id, $h->text), $headings);
            $title = '<h2 id="'.htmlspecialchars($anchor, \ENT_QUOTES).'" class="doc-part-title">'.htmlspecialchars($page->title, \ENT_QUOTES).'<a href="#'.htmlspecialchars($anchor, \ENT_QUOTES).'" class="heading-anchor" aria-hidden="true" tabindex="-1" title="Permalink"></a></h2>';
            $html = '<section class="doc-part" data-path="'.htmlspecialchars($node->path, \ENT_QUOTES).'">'.$title."\n".$html.'</section>';
            array_unshift($headings, new Heading(2, $anchor, $page->title));
        } else {
            $html = '<section class="doc-part doc-part-home" data-path="">'.$html.'</section>';
        }
        $html = str_replace(self::MARK, '#', $html);

        return new OnePart($node->path, $page->title, $anchor, $html, $headings, $page->source, '' !== $page->text() ? $page->text() : Text::of($page->html()), $page);
    }
}
