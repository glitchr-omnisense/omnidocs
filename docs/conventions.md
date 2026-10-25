# The HTML conventions

Every source writes the same HTML (`Html\Conventions`), the conventions of symfony.com/doc:

| What | HTML | Markdown (omnidocs/markdown) |
|---|---|---|
| a callout | `<blockquote class="doc-callout doc-callout-note" data-label="Note">` | `> [!NOTE]`, `[!TIP]`, `[!IMPORTANT]`, `[!WARNING]`, `[!CAUTION]`, `[!SEEALSO]` |
| new in a version | `<blockquote class="doc-callout doc-callout-versionadded" data-label="New in version 1.4" data-version="1.4">` | `> [!VERSIONADDED] 1.4` |
| deprecated | `... doc-callout-deprecated ... data-version="2.0"` | `> [!DEPRECATED] 2.0` |
| a version badge | `<span class="doc-version doc-version-versionadded" data-version="1.1">` | a heading ending with a known version: `### Keys (1.1)` |
| a code frame | `<div class="doc-code" data-lang="js"><div class="doc-code-caption">app.js</div><pre><code class="language-js">` | ```` ```js caption="app.js" ```` |
| code tabs | `<div class="doc-tabs" data-doc-tabs>`, a tab list, a panel each | blocks that follow each other, each ```` ```yaml tab="YAML" ```` |

The labels are English by default; `new Conventions(['note' => 'Remarque', 'versionadded' =>
'Nouveau en %version%'])` (a provider's `labels` option) says them in the reader's language.
Sphinx's admonitions, versionadded and highlighted blocks, Doxygen's \note, \warning, \see, \since
and code examples become the same.
