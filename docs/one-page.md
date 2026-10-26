# One page per manual, the sidebar, the search

`OnePage` reads a version as one page, at one address: the home first, as it is, then every page as a
part - a `<section class="doc-part">` whose title is an `<h2 id="<part>">`, its own headings one level
down (its h2 an h3), every id of the part prefixed with the part's anchor (`keys` becomes
`sticky-keys`). A link from a page to another page becomes an anchor of the page (`#sticky`,
`#sticky-keys`); files and pictures keep the linker's addresses.

```php
$one = new OnePage($version, $links, '/docs/flowjs/1.x');
foreach ($one->parts() as $part) {
    echo $part->html;                 // and an "edit this page" link to $part->source
}
$one->toc();                          // [['heading' => Heading, 'children' => [Heading...]], ...]
$one->redirects();                    // ['sticky' => ['anchor' => 'sticky', 'prefix' => 'sticky-'], ...]
$one->urlOf('sticky', 'keys');        // /docs/flowjs/1.x#sticky-keys
```

The former address of each page redirects to its part: with omnibridge/static,
`StaticResponse::redirect('/docs/flowjs/1.x#sticky', 301, fragment: 'sticky-')` (or a host response's
`X-Static-Fragment: sticky-` header) writes a page whose script sends `/docs/flowjs/1.x/sticky#keys`
to `/docs/flowjs/1.x#sticky-keys`.

## The sidebar

`assets/omnidocs.css` lays the page out: `.omnidocs-layout` holds `.omnidocs-sidebar` beside
`.omnidocs-content`; the sidebar sticks (`position: sticky`, `--omnidocs-top` below the top) with its
own scroll when it is taller than the screen; under 64rem it is a drawer opened by
`[data-omnidocs-toc-toggle]`. `@Omnidocs/toc.html.twig` writes the table of contents
(`[data-omnidocs-toc]`); `assets/omnidocs.js` marks the heading being read (`.is-current`,
`aria-current="location"`), opens its section, keeps it in sight in the sidebar, and keeps the address
on it with `history.replaceState` - the back button is not filled with sections.

An entry with entries under it is a **disclosure**: its dot and its name form a button
(`[data-omnidocs-toc-disclose]`, `aria-expanded`, `aria-controls` naming its list), and the section
itself is reached by the small arrow beside it (`a.omnidocs-toc-go`, named "Go to …" - the template's
`go` variable gives the words). By default only the entry being read is open, and the one open follows
the reading; an entry the reader opened or closed by hand stays so until they change it. Enter and
Space toggle it (it is a button), ArrowRight opens it, ArrowLeft closes it; it opens with a short
animation, none under `prefers-reduced-motion`; the phone's drawer is the same list. Without the
script every entry stays open.

```twig
{% include '@Omnidocs/toc.html.twig' with {toc: one.toc(), label: 'On this page', go: 'Aller à'} %}
```

**Groups.** `new OnePage($version, $links, $url, groups: true)` takes the manual's top-level sections
(a folder of Markdown files, a toctree caption, a group of API entries) as groups of its parts: the
table of contents lists each group with its pages under it (`'group' => true` on the entry), and the
page puts the group's title before its first part (`<div class="doc-group" id="group-navigation"><p
class="doc-group-title">Navigation</p></div>`). A section of a single page that bears its name is that
page alone. `groups()` lists them: `[['id' => 'group-navigation', 'title' => 'Navigation', 'paths' =>
['transparent', 'flowframe']], ...]`.

A site gives its look through custom properties: `--omnidocs-bg`, `--omnidocs-fg`, `--omnidocs-muted`,
`--omnidocs-line`, `--omnidocs-soft`, `--omnidocs-accent`, `--omnidocs-top`, `--omnidocs-drawer-top`,
`--omnidocs-sidebar`, `--omnidocs-mono` (the defaults have no specificity: any rule of the site wins).

## The search

`Search\SearchIndex` writes one JSON file the browser downloads whole, the first time the reader
searches: `onePage()` gives a record for the page, one per part, per heading (levels 2 and 3), per API
entry - each leading to its anchor - and `add()` takes the site's own (`Entry`: kind, title, address,
text, headings, extra words, page, signature; short keys `k t u d h x p s`).

`@Omnidocs/search.html.twig` writes the button, `assets/omnidocs.js` the palette: ⌘ K (Ctrl K off a
Mac) or `/` opens it from anywhere, every word must be found (accents and case ignored), the title
counts more, an API entry named by its last part leads; arrows move, Enter opens, Escape closes. The
browser's find in page (⌘ F, Ctrl F) is never taken: one page holds the whole manual, so it finds
everything there too.
