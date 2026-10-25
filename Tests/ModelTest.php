<?php

namespace Omnidocs\Tests;

use Omnidocs\Model\Manual;
use Omnidocs\Model\Node;
use Omnidocs\Model\Rendered;
use Omnidocs\Model\Version;
use Omnidocs\Testing\ArraySource;
use Omnidocs\Testing\RecordingLinker;
use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
    public function testAVersionReadsItsPagesHomeFirstThenDepthFirst(): void
    {
        $source = new class implements \Omnidocs\SourceInterface {
            public function home(): ?Node
            {
                return new Node('', 'Home', 'README.md', 0);
            }

            public function tree(): array
            {
                return [
                    new Node('install', 'Install', 'docs/install.md', 1),
                    Node::section('guides', 'Guides', [new Node('guides/stops', 'Stops', 'docs/guides/stops.md'), new Node('guides/keys', 'Keys', 'docs/guides/keys.md')], 2),
                    new Node('faq', 'FAQ', 'docs/faq.md', 3),
                ];
            }

            public function page(string $path, ?\Omnidocs\LinkerInterface $links = null): ?\Omnidocs\Model\Page
            {
                return new \Omnidocs\Model\Page($path, $path, new Rendered('<p>'.$path.'</p>'));
            }

            public function symbols(): array
            {
                return [];
            }
        };
        $version = new Version('1.x', $source, '1.x', 'abc');

        self::assertSame(['', 'install', 'guides/stops', 'guides/keys', 'faq'], array_map(static fn (Node $n): string => $n->path, $version->pages()));
        self::assertNull($version->previous(''));
        self::assertSame('guides/stops', $version->next('install')?->path);
        self::assertSame('install', $version->previous('guides/stops')?->path);
        self::assertNull($version->next('faq'));
        self::assertNull($version->page('guides'), 'a section is no page');
        self::assertSame('<p>faq</p>', $version->page('faq')?->html());
        self::assertTrue($version->tree()[1]->contains('guides/keys'));
        self::assertTrue($version->tree()[1]->isSection());
    }

    public function testAManualReadsItsDefaultVersion(): void
    {
        $a = new Version('1.x', new ArraySource(['' => ['title' => 'One', 'html' => '']]));
        $b = new Version('2.x', new ArraySource(['' => ['title' => 'Two', 'html' => '']]));
        $manual = new Manual('flowjs', 'Flow', [$a, $b], '2.x', 'glitchr-studio/flowjs');

        self::assertSame(['1.x', '2.x'], $manual->series());
        self::assertSame($b, $manual->version());
        self::assertTrue($manual->isCurrent('2.x'));
        self::assertFalse($manual->isCurrent('1.x'));
        self::assertTrue($manual->has('1.x'));
        $this->expectException(\OutOfBoundsException::class);
        $manual->version('3.x');
    }

    public function testAManualWithoutTheDefaultItNamesIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Manual('flowjs', 'Flow', [new Version('1.x', new ArraySource([]))], '2.x');
    }

    public function testAnExcerptIsCutAtAWordWithAnEllipsis(): void
    {
        $rendered = new Rendered('', null, [], [], "Scroll   and navigation\nfor pages that are server-rendered");

        self::assertSame('Scroll and navigation for pages that are server-rendered', $rendered->excerpt());
        self::assertSame('Scroll and…', $rendered->excerpt(12));
    }

    public function testTheArraySourceLinksItsPagesThroughTheLinker(): void
    {
        $links = new RecordingLinker();
        $page = (new ArraySource(['' => ['title' => 'Home', 'html' => '<a href="page:install#npm">npm</a>'], 'install' => ['title' => 'Install', 'html' => '']]))->page('', $links);

        self::assertSame('<a href="/install#npm">npm</a>', $page?->html());
        self::assertSame([['install', 'npm']], $links->pages);
    }
}
