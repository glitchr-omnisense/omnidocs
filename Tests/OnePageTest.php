<?php

namespace Omnidocs\Tests;

use Omnidocs\Model\Symbol;
use Omnidocs\Model\Version;
use Omnidocs\OnePage;
use Omnidocs\Search\SearchIndex;
use Omnidocs\Testing\ArraySource;
use Omnidocs\Testing\RecordingLinker;
use PHPUnit\Framework\TestCase;

final class OnePageTest extends TestCase
{
    private function one(): OnePage
    {
        return new OnePage(new Version('1.x', new ArraySource([
            '' => ['title' => 'Flow', 'html' => '<p>Read <a href="page:sticky#keys">the keys</a>.</p><h2 id="use">Use<a href="#use" class="heading-anchor"></a></h2>', 'source' => 'README.md'],
            'sticky' => ['title' => 'Sticky', 'html' => '<h2 id="keys">Keys<a href="#keys" class="heading-anchor"></a></h2><h3 id="ends">Ends</h3><p><a href="#ends">up</a> <a href="page:">home</a> <a href="page:guides/use">use</a></p>', 'source' => 'docs/sticky.md'],
            'guides/use' => ['title' => 'Use', 'html' => '<h2 id="use">Again</h2>'],
        ], [new Symbol('function', 'StickyStops.attach', 'sticky', 'keys')])), new RecordingLinker(), '/docs/flowjs/1.x');
    }

    public function testEveryPageIsAPartOfOnePageItsHeadingsOneLevelDown(): void
    {
        $one = $this->one();
        $parts = $one->parts();

        self::assertSame(['', 'sticky', 'guides/use'], array_map(static fn ($p) => $p->path, $parts));
        self::assertSame(['', 'sticky', 'guides-use'], array_map(static fn ($p) => $p->anchor, $parts));
        self::assertStringContainsString('<a href="#sticky-keys">the keys</a>', $parts[0]->html);
        self::assertStringContainsString('<h2 id="use">Use', $parts[0]->html, 'the home as it is');
        self::assertStringContainsString('<h2 id="sticky" class="doc-part-title">Sticky<a href="#sticky"', $parts[1]->html);
        self::assertStringContainsString('<h3 id="sticky-keys">Keys<a href="#sticky-keys" class="heading-anchor"></a></h3><h4 id="sticky-ends">Ends</h4>', $parts[1]->html);
        self::assertStringContainsString('<a href="#sticky-ends">up</a> <a href="#">home</a> <a href="#guides-use">use</a>', $parts[1]->html);
        self::assertStringContainsString('<h3 id="guides-use-use">Again</h3>', $parts[2]->html, 'an id the home has too, prefixed: no two ids meet');
        self::assertSame('docs/sticky.md', $parts[1]->source);
    }

    public function testTheTableOfContentsAndTheFormerAddresses(): void
    {
        $one = $this->one();

        self::assertSame([['use', ['']], ['sticky', ['sticky-keys']], ['guides-use', ['guides-use-use']]], array_map(static fn (array $e): array => [$e['heading']->id, array_map(static fn ($h) => $h->id, $e['children']) ?: ['']], $one->toc()));
        self::assertSame(['sticky' => ['anchor' => 'sticky', 'prefix' => 'sticky-'], 'guides/use' => ['anchor' => 'guides-use', 'prefix' => 'guides-use-']], $one->redirects());
        self::assertSame('/docs/flowjs/1.x#sticky-keys', $one->urlOf('sticky', 'keys'));
        self::assertSame('/docs/flowjs/1.x', $one->urlOf(''));
    }

    public function testItsSearchRecordsLeadToTheirAnchors(): void
    {
        $entries = (new SearchIndex())->onePage($this->one(), 'flowjs', extra: '1.x')->toArray()['entries'];

        self::assertSame(['page', 'heading', 'section', 'heading', 'section', 'heading', 'symbol'], array_column($entries, 'k'));
        self::assertSame('/docs/flowjs/1.x', $entries[0]['u']);
        self::assertSame('/docs/flowjs/1.x#sticky', $entries[2]['u']);
        self::assertSame(['k' => 'heading', 't' => 'Keys', 'u' => '/docs/flowjs/1.x#sticky-keys', 'd' => 'Ends up home use', 'x' => '1.x', 'p' => 'Sticky'], $entries[3]);
        self::assertSame('/docs/flowjs/1.x#sticky-keys', $entries[6]['u']);
    }
}
