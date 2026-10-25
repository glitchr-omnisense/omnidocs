# The model and the sources

| Class | What it is |
|---|---|
| `Model\Manual` | a key, a title, its versions by name, the default one, its repository |
| `Model\Version` | a name, a source, the branch it follows, the commit it is read at, its end of life; its pages in reading order (the home, then the tree depth first), `previous()` / `next()` |
| `Model\Node` | a place in the contents: a page (its file in the repository) or a section (`isSection()`) holding pages |
| `Model\Page` | a page rendered: path, title, `Rendered` (HTML, title, headings, metadata, plain text), source file, its API entries |
| `Model\Heading` | level, id, text |
| `Model\Symbol` | an API entry: kind ("class", "method", "option", "py:function"...), name, page, anchor, summary, signature, parent, source line |

`SourceInterface` asks three things of a format: `home()` and `tree()` (the contents), `page($path,
$links)` (a page rendered, its links through the linker), `symbols()` (the API entries). A source reads
files that exist - a checkout, a tool's output - and never runs the tool nor reaches the network.

A provider's factory extends `SourceFactory`: its defaults in `populate()`, its required options in
`omnidocs.required_options`, the source built in `build()`. Its tests use
`Testing\SourceContractTests` on a real tool's output:

- the contents are nodes, one path each, the home at "", sections hold pages and no file;
- every page renders, with a title, no script, and each heading it lists at its anchor;
- a path that is no page gives none;
- its links to pages go to pages it has;
- every API entry is on a page at its anchor (a source that documents an API must give entries);
- a page read twice is the same.
