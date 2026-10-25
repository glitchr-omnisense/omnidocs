<?php

namespace Omnidocs\Tests;

use Omnidocs\Html\Conventions;
use Omnidocs\Html\Headings;
use Omnidocs\Html\Links;
use Omnidocs\Html\Text;
use Omnidocs\Linking\RepositoryLinker;
use Omnidocs\Support\Path;
use PHPUnit\Framework\TestCase;

final class HtmlTest extends TestCase
{
    public function testACalloutIsTheBlockquoteSymfonysDocsStyle(): void
    {
        $conventions = new Conventions(['note' => 'Remarque', 'versionadded' => 'Nouveau en %version%']);

        self::assertSame('<blockquote class="doc-callout doc-callout-note" data-label="Remarque"><p>Hi</p></blockquote>', $conventions->callout('note', '<p>Hi</p>'));
        self::assertSame('<blockquote class="doc-callout doc-callout-versionadded" data-label="Nouveau en 1.4" data-version="1.4"><p>ends</p></blockquote>', $conventions->callout('versionadded', '<p>ends</p>', '1.4'));
        self::assertSame('Tip', $conventions->label('tip'));
        self::assertSame('Deprecated since version 2.0', $conventions->label('deprecated', '2.0'));
        self::assertSame('<span class="doc-version doc-version-versionadded" data-version="1.1" title="New in version 1.1">1.1</span>', (new Conventions())->versionBadge('1.1'));
    }

    public function testCodeHasItsFrameItsLanguageAndItsCaption(): void
    {
        $conventions = new Conventions();

        self::assertSame('<div class="doc-code" data-lang="js"><div class="doc-code-caption">app.js</div><pre class="notranslate"><code class="language-js">a &amp;&amp; b</code></pre></div>', $conventions->code('a &amp;&amp; b', 'js', 'app.js'));
        self::assertSame('<div class="doc-code"><pre class="notranslate"><code>x</code></pre></div>', $conventions->code('x'));
    }

    public function testTabsHaveATabAndAPanelEach(): void
    {
        $html = (new Conventions())->tabs(['npm' => '<p>npm i</p>', 'yarn' => '<p>yarn add</p>'], 'tabs-1');

        self::assertStringContainsString('<button type="button" role="tab" id="tabs-1-tab-0" aria-controls="tabs-1-panel-0" aria-selected="true">npm</button>', $html);
        self::assertStringContainsString('<div class="doc-tab" role="tabpanel" id="tabs-1-panel-1" aria-labelledby="tabs-1-tab-1" data-tab="yarn"><p>yarn add</p></div>', $html);
    }

    public function testHeadingsAreReadWithTheirIdsOnThemOrOnTheirSection(): void
    {
        $html = '<h1 id="top">Top</h1><h2 id="use">Use <a class="headerlink" href="#use">¶</a></h2><section id="stops"><h3>Stops &amp; keys<a class="headerlink" href="#stops">¶</a></h3></section><h2>No id</h2><h5 id="deep">Deep</h5>';
        $headings = Headings::of($html);

        self::assertSame([['level' => 2, 'id' => 'use', 'text' => 'Use'], ['level' => 3, 'id' => 'stops', 'text' => 'Stops & keys']], array_map(static fn ($h) => $h->toArray(), $headings));
        $taken = [];
        self::assertSame('scroll-stops', Headings::id('Scroll stops', $taken));
        self::assertSame('scroll-stops-1', Headings::id('Scroll stops', $taken));
        self::assertSame('défilement', Headings::id('Défilement', $taken));
    }

    public function testTheTextOfHtmlIsWhatAReaderReads(): void
    {
        self::assertSame('One Two: a & b.', Text::of('<p>One</p><p>Two: <code>a &amp; b</code>.</p><script>x()</script>'));
    }

    public function testLinksAreRewrittenOrKept(): void
    {
        $html = Links::rewrite('<a class="x" href="install.html#npm">i</a> <img alt="" src="_images/a.png"> <a href="https://example.org">e</a>', static fn (string $url, bool $image): ?string => match (true) {
            $image => '/img/'.$url,
            str_starts_with($url, 'https:') => null,
            default => '/docs/'.$url,
        });

        self::assertSame('<a class="x" href="/docs/install.html#npm">i</a> <img alt="" src="/img/_images/a.png"> <a href="https://example.org">e</a>', $html);
    }

    public function testPathsInsideARepository(): void
    {
        self::assertSame('src/Foo.php', Path::normalise('docs/../src/./Foo.php'));
        self::assertNull(Path::normalise('../outside'));
        self::assertSame('README.md', Path::resolve('docs/sticky.md', '../README.md'));
        self::assertSame(['guides/install.md', 'npm'], Path::split('guides/install.md?x=1#npm'));
        self::assertTrue(Path::isAbsolute('#top'));
        self::assertTrue(Path::isAbsolute('mailto:a@b.c'));
        self::assertFalse(Path::isAbsolute('sticky.md'));
        self::assertSame([10, 'getting-started'], Path::order('10-getting-started'));
        self::assertSame('Getting started', Path::humanise('getting-started'));
    }

    public function testARepositoryLinkerPointsAtTheCommitRead(): void
    {
        $links = new RepositoryLinker(static fn (string $path, ?string $fragment): string => '/docs/'.$path.($fragment ? '#'.$fragment : ''), 'glitchr-studio/flowjs', 'c417356');

        self::assertSame('/docs/sticky#stops', $links->page('sticky', 'stops'));
        self::assertSame('https://github.com/glitchr-studio/flowjs/blob/c417356/src/js/stops.js#L12', $links->file('src/js/stops.js', 'L12'));
        self::assertSame('https://raw.githubusercontent.com/glitchr-studio/flowjs/c417356/docs/img/a%20b.png', $links->image('docs/img/a b.png'));
        self::assertSame('https://github.com/glitchr-studio/flowjs/edit/master/docs/sticky.md', $links->edit('docs/sticky.md', 'master'));
        self::assertNull((new RepositoryLinker(static fn (): string => '/'))->file('x'));
    }
}
