<?php

namespace Omnidocs\Tests;

use Omnidocs\Model\Heading;
use Omnidocs\Model\Symbol;
use Omnidocs\Model\Version;
use Omnidocs\Search\Entry;
use Omnidocs\Search\SearchIndex;
use Omnidocs\Testing\ArraySource;
use Omnidocs\Testing\RecordingLinker;
use PHPUnit\Framework\TestCase;

final class SearchIndexTest extends TestCase
{
    public function testPagesHeadingsAndSymbolsAreRecordsInTheirOrder(): void
    {
        $version = new Version('1.x', new ArraySource([
            '' => ['title' => 'Flow', 'html' => '<p>Scroll and navigation.</p>'],
            'sticky' => ['title' => 'Sticky and stops', 'html' => '<h2 id="keys">The keys</h2><p>An arrow goes from stop to stop.</p><h4 id="deep">Deep</h4>'],
        ], [new Symbol('function', 'StickyStops.attach', 'sticky', 'keys', 'The stops of a scroller.', 'attach(scroller, options)', 'StickyStops')]));
        $index = (new SearchIndex())->version($version, new RecordingLinker(), extra: 'flowjs 1.x');

        self::assertSame([
            'entries' => [
                ['k' => 'page', 't' => 'Flow', 'u' => '/', 'd' => 'Scroll and navigation.', 'x' => 'flowjs 1.x'],
                ['k' => 'page', 't' => 'Sticky and stops', 'u' => '/sticky', 'd' => 'The keys An arrow goes from stop to stop. Deep', 'h' => 'The keys', 'x' => 'flowjs 1.x'],
                ['k' => 'heading', 't' => 'The keys', 'u' => '/sticky#keys', 'd' => 'An arrow goes from stop to stop. Deep', 'x' => 'flowjs 1.x', 'p' => 'Sticky and stops'],
                ['k' => 'symbol', 't' => 'StickyStops.attach', 'u' => '/sticky#keys', 'd' => 'The stops of a scroller.', 'x' => 'function flowjs 1.x', 'p' => 'Sticky and stops', 's' => 'attach(scroller, options)'],
            ],
        ], $index->toArray());
    }

    public function testASiteAddsItsOwnRecords(): void
    {
        $index = (new SearchIndex())->add(Entry::page('guide', 'Getting started', '/guides/start', 'First steps', [new Heading(2, 'a', 'Install'), new Heading(4, 'b', 'Deep')]), new Entry('news', 'Released', '/news/1', extra: '2026-10-25'));

        self::assertSame('{"locale":"fr","entries":[{"k":"guide","t":"Getting started","u":"/guides/start","d":"First steps","h":"Install"},{"k":"news","t":"Released","u":"/news/1","x":"2026-10-25"}]}', $index->toJson('fr'));
    }
}
