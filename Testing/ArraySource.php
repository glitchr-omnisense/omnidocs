<?php

namespace Omnidocs\Testing;

use Omnidocs\Html\Headings;
use Omnidocs\Html\Text;
use Omnidocs\LinkerInterface;
use Omnidocs\Model\Node;
use Omnidocs\Model\Page;
use Omnidocs\Model\Rendered;
use Omnidocs\Model\Symbol;
use Omnidocs\SourceInterface;

/**
 * A manual written out in PHP, for an application's tests or a page that
 * is no file: pages as [path => ['title' => ..., 'html' => ...]] (the order
 * given is the reading order; "" is the home), symbols as Symbol objects.
 * A link written "page:<path>#<fragment>" in a page's HTML goes through the
 * linker.
 *
 *   new ArraySource(['' => ['title' => 'Home', 'html' => '<p>Hi</p>'], 'install' => ['title' => 'Install', 'html' => '<h2 id="npm">npm</h2>']]);
 */
final class ArraySource implements SourceInterface
{
    /**
     * @param array<string, array{title: string, html: string, source?: string}> $pages
     * @param list<Symbol>                                                       $symbols
     */
    public function __construct(private readonly array $pages, private readonly array $symbols = [])
    {
    }

    public function home(): ?Node
    {
        return isset($this->pages['']) ? new Node('', $this->pages['']['title'], $this->pages['']['source'] ?? null, 0) : null;
    }

    public function tree(): array
    {
        $tree = [];
        $order = 0;
        foreach ($this->pages as $path => $page) {
            if ('' !== (string) $path) {
                $tree[] = new Node((string) $path, $page['title'], $page['source'] ?? null, ++$order);
            }
        }

        return $tree;
    }

    public function page(string $path, ?LinkerInterface $links = null): ?Page
    {
        $page = $this->pages[$path] ?? null;
        if (null === $page) {
            return null;
        }
        $html = (string) preg_replace_callback('/href="page:([^"#]*)(?:#([^"]*))?"/', static fn (array $m): string => 'href="'.htmlspecialchars(null !== $links ? $links->page($m[1], '' !== ($m[2] ?? '') ? $m[2] : null) : '#', \ENT_QUOTES).'"', $page['html']);

        return new Page(
            $path,
            $page['title'],
            new Rendered($html, $page['title'], Headings::of($html), [], Text::of($html)),
            $page['source'] ?? null,
            array_values(array_filter($this->symbols, static fn (Symbol $s): bool => $s->page === $path)),
        );
    }

    public function symbols(): array
    {
        return $this->symbols;
    }
}
