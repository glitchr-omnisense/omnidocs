<?php

namespace Omnidocs\Testing;

use Omnidocs\Model\Node;
use Omnidocs\SourceInterface;

/**
 * What every source does, as PHPUnit tests: a provider's test case (or an
 * application's, for a source of its own) uses this trait and gives the
 * source, read from a real tool's output.
 *
 *   final class SphinxSourceTest extends TestCase
 *   {
 *       use SourceContractTests;
 *
 *       protected function source(): SourceInterface { return (new SphinxSourceFactory())->create(['dir' => __DIR__.'/Fixtures/requests']); }
 *   }
 *
 * Requires phpunit/phpunit (the application's, in require-dev).
 */
trait SourceContractTests
{
    /** The source under test; called once per test. */
    abstract protected function source(): SourceInterface;

    /** Whether the source documents an API: then it must give its entries. */
    protected function documentsSymbols(): bool
    {
        return false;
    }

    /** @return list<Node> */
    private function contractPages(SourceInterface $source): array
    {
        $home = $source->home();

        return [...(null !== $home ? [$home] : []), ...Node::flatten($source->tree())];
    }

    public function testItsContentsAreNodesWithOnePathEach(): void
    {
        $source = $this->source();
        $home = $source->home();
        if (null !== $home) {
            self::assertSame('', $home->path, 'the home is the page at ""');
            self::assertFalse($home->isSection());
        }
        $paths = [];
        $walk = static function (array $nodes) use (&$walk, &$paths): void {
            foreach ($nodes as $node) {
                self::assertInstanceOf(Node::class, $node);
                self::assertNotSame('', $node->path, 'only the home is at ""');
                self::assertNotSame('', trim($node->title), 'a node has a title: '.$node->path);
                if ($node->isSection()) {
                    self::assertNull($node->source, 'a section is read from no file: '.$node->path);
                    self::assertNotSame([], $node->children, 'a section holds pages: '.$node->path);
                }
                $paths[] = $node->path;
                $walk($node->children);
            }
        };
        $walk($source->tree());
        self::assertNotSame([], $this->contractPages($source), 'a source has pages');
        self::assertSame(array_unique($paths), $paths, 'one node per path');
    }

    public function testEveryPageRendersWithTheAnchorsItLists(): void
    {
        $source = $this->source();
        foreach ($this->contractPages($source) as $node) {
            $page = $source->page($node->path, new RecordingLinker());
            self::assertNotNull($page, 'the page of the node '.$node->path);
            self::assertSame($node->path, $page->path);
            self::assertNotSame('', trim($page->title), 'a page has a title: '.$node->path);
            self::assertDoesNotMatchRegularExpression('#<script\b#i', $page->html(), 'a source writes no script: '.$node->path);
            foreach ($page->headings() as $heading) {
                self::assertNotSame('', $heading->id, 'a heading listed has its id: '.$node->path);
                self::assertStringContainsString('id="'.htmlspecialchars($heading->id, \ENT_QUOTES).'"', $page->html(), 'the heading "'.$heading->text.'" is at its anchor in '.$node->path);
            }
        }
    }

    public function testAPathThatIsNoPageGivesNone(): void
    {
        self::assertNull($this->source()->page('no-such-page/at-all-'.bin2hex(random_bytes(3)), new RecordingLinker()));
    }

    public function testItsLinksToItsOwnPagesGoToPagesItHas(): void
    {
        $source = $this->source();
        $known = array_map(static fn (Node $n): string => $n->path, $this->contractPages($source));
        foreach ($this->contractPages($source) as $node) {
            $links = new RecordingLinker();
            $source->page($node->path, $links);
            foreach ($links->pages as [$path]) {
                self::assertContains($path, $known, 'the page '.$node->path.' links to a page the source has');
            }
        }
    }

    public function testEveryApiEntryIsOnAPageAtItsAnchor(): void
    {
        $source = $this->source();
        $symbols = $source->symbols();
        if ($this->documentsSymbols()) {
            self::assertNotSame([], $symbols, 'an API reference has entries');
        }
        self::assertIsList($symbols);
        $html = [];
        foreach ($symbols as $symbol) {
            self::assertNotSame('', trim($symbol->name));
            self::assertNotSame('', trim($symbol->kind));
            $html[$symbol->page] ??= $source->page($symbol->page, new RecordingLinker())?->html();
            self::assertNotNull($html[$symbol->page], 'the page of '.$symbol->name.': '.$symbol->page);
            if (null !== $symbol->anchor) {
                self::assertStringContainsString('id="'.htmlspecialchars($symbol->anchor, \ENT_QUOTES).'"', $html[$symbol->page], $symbol->name.' is at its anchor');
            }
        }
    }

    public function testAPageReadTwiceIsTheSame(): void
    {
        $source = $this->source();
        foreach (\array_slice($this->contractPages($source), 0, 20) as $node) {
            self::assertSame($source->page($node->path, new RecordingLinker())?->html(), $this->source()->page($node->path, new RecordingLinker())?->html(), 'the same HTML: '.$node->path);
        }
    }
}
