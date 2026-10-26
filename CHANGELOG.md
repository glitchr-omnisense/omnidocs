# Changelog

Every version of omnisense/omnidocs, newest first ([Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
[Semantic Versioning](https://semver.org/)).

## Unreleased

### Changed
- The package is `omnisense/omnidocs` (Omniscient is now Omnisense), its repository `glitchr-omnisense/omnidocs`; `replace` keeps `omniscient/omnidocs` resolving.

### Added
- `OnePage(..., groups: true)`: the manual's top-level sections group its parts - in the table of
  contents (a group, its pages under it; a section of one page that bears its name is that page) and
  in the page (`div.doc-group`, its title, its anchor, before the group's first part). `groups()`.
- The table of contents' disclosures (`Bridge/Twig/templates/toc.html.twig`, `assets/omnidocs.js`):
  an entry with entries under it is a button - its dot and its name, `aria-expanded`,
  `aria-controls` - and its section is reached by the small arrow beside it (`.omnidocs-toc-go`,
  "Go to …", the `go` variable). Only the entry being read is open, following the reading; what the
  reader opens or closes by hand stays so until they change it; Enter and Space toggle, ArrowRight
  opens, ArrowLeft closes; a short animation, none under reduced motion; the same in a phone's drawer.
  Without the script every entry stays open.
- The model (Manual, Version, Node, Page, Rendered, Heading, Symbol), `SourceInterface`, the factories'
  `Registry` and `Config`, `LinkerInterface` and `RepositoryLinker`.
- `OnePage`: a version read as one page, its parts, its table of contents, its former addresses.
- `Search\SearchIndex`, `Html\Conventions`, `Html\Headings`, `Html\Links`, `Html\Text`, `Html\Prose`.
- `assets/omnidocs.js`, `assets/omnidocs.css`, `Bridge/Twig/templates`.
- `Testing\SourceContractTests`, `Testing\ArraySource`, `Testing\RecordingLinker`.
- The Docker harness: the packages' suites, the tools (Sphinx, Doxygen, JSDoc) on real projects.
