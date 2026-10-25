# omniscient/omnidocs

One model of a manual, whatever wrote it. A manual has versions; a version has a home, its contents
(pages, and sections holding pages), its pages - a title, an HTML body, the headings with their
anchors, the file to edit - and its API entries (`Symbol`: a class, a function, an option... on a page,
at an anchor). Each format is a provider that implements one `SourceInterface`:

| Package | Reads | Verified on |
|---|---|---|
| [omnidocs/markdown](https://github.com/glitchr-studio/omnidocs-markdown) | a folder of Markdown files, written as symfony.com/doc writes its pages | flowjs' docs/, README, CHANGELOG; the Omniscient site's guides, news and 153 manuals |
| [omnidocs/php](https://github.com/glitchr-studio/omnidocs-php) | a PHP package's sources: its API reference | this package; the 150 PHP packages of the Omniscient catalogue |
| [omnidocs/jsdoc](https://github.com/glitchr-studio/omnidocs-jsdoc) | the JSON `jsdoc -X` writes | flowjs (seven UMD modules) |
| [omnidocs/sphinx](https://github.com/glitchr-studio/omnidocs-sphinx) | Sphinx's JSON builder output and objects.inv | Requests v2.32.3 |
| [omnidocs/doxygen](https://github.com/glitchr-studio/omnidocs-doxygen) | Doxygen's XML output | TinyXML-2 10.0.0 |

What the core adds over the sources:

- `OnePage`: a version read as **one page** - the home, then every page as a part, its headings one
  level down and its ids prefixed so that none meet, links between pages turned into anchors of the
  page, the table of contents (h2/h3) for a sidebar, and the former address of each page with the
  part it became, for a static redirection that carries the visitor's `#fragment` over
  ([omnibridge/static](https://github.com/glitchr-studio/omnibridge-static));
- `Search\SearchIndex`: the JSON a static page searches - a record per page (or part), per heading,
  per API entry, plus the site's own;
- `Html\Conventions`: the HTML every source writes - callouts (note, tip, warning, seealso,
  versionadded, deprecated), version badges, code frames with captions, code tabs;
- `assets/omnidocs.js` and `assets/omnidocs.css`: the reader's side - the search palette (⌘ K, Ctrl K
  or `/`; the browser's ⌘ F is left alone), the sticky table of contents that follows the reading and
  keeps the address on the section read (history.replaceState: nothing added to the history), its
  drawer on a phone, code tabs, copy buttons; `Bridge/Twig/templates` the table of contents and the
  search button as Twig partials;
- `Testing\SourceContractTests`: the conformance suite every source passes.

It requires PHP 8.2 alone: no framework, no template engine.

```php
use Omnidocs\Linking\RepositoryLinker;
use Omnidocs\Markdown\MarkdownSourceFactory;
use Omnidocs\Model\Manual;
use Omnidocs\Model\Version;
use Omnidocs\OnePage;
use Omnidocs\Registry;
use Omnidocs\Search\SearchIndex;

$docs = new Registry([new MarkdownSourceFactory()], [
    'manual' => ['factory' => 'markdown', 'options' => ['root' => '/path/to/flowjs', 'home' => 'README.md']],
]);
$manual = new Manual('flowjs', 'Flow', [new Version('1.x', $docs->get('manual'), 'master', 'c417356')]);
$links = new RepositoryLinker(fn (string $path, ?string $anchor) => '/docs/'.$path.($anchor ? '#'.$anchor : ''), 'glitchr-studio/flowjs', 'c417356');

$one = new OnePage($manual->version(), $links, '/docs');
$one->parts();   // the home, then each page: title, anchor, HTML, the file to edit
$one->toc();     // the sidebar
file_put_contents('search.json', (new SearchIndex())->onePage($one, 'Flow')->toJson('en'));
```

## Documentation

- [Installation and a manual in plain PHP](docs/installation.md)
- [The model and the sources](docs/model.md)
- [One page per manual, the sidebar, the search](docs/one-page.md)
- [The HTML conventions](docs/conventions.md)
- [The Docker harness](docs/harness.md)

## Verified, and not

- Verified (`docker compose run --rm omnidocs test` in `docker/`, PHP 8.4): every package's suite -
  the conformance suite on each provider's real fixture, the core's own model, one page, search
  index, conventions, registry; `BareTest` in each package (every class loaded with Composer's
  autoloader alone, no framework class); `docker/harness/bin/bare` (a manual built by hand, its pages,
  its search index, nothing loaded but omnidocs); `node --test Tests/js/omnidocs.test.cjs` (the
  palette's ranking, marks, and ⌘ F left to the browser).
- Verified on the tools' whole output (`docker compose run --rm sphinx`, `doxygen`, `jsdoc`, then
  `omnidocs real`): Requests' 15 documents and the 167 API entries of its 223-line inventory (its
  std: labels left out), TinyXML-2's main page and 24 compounds with 407 entries, flowjs' 3 988
  doclets read as 7 globals and 299 public entries.
- Verified in use: the Omniscient site (153 manuals, one page each, 150 PHP API references) and the
  flowjs site, built statically and read in headless Chrome.
- Not verified: Sphinx themes' own JSON additions beyond the standard builder; Sphinx projects
  written with MyST (Markdown) - the JSON builder writes the same HTML, not tried; Doxygen's C#,
  Java and Python output; TypeDoc (see omnidocs/jsdoc's README: why JSDoc).

Licence: MIT.
