# Changelog

Every version of omnisense/omnidocs, newest first ([Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
[Semantic Versioning](https://semver.org/)).

## Unreleased

### Changed

- PHPStan max green again over the 2026-10-21 baseline, types only: `OnePage::toc()`'s documented
  shape names the `group` key a group's entry has (with `$groups`), its entries built as typed rows;
  the group anchors reserve the parts' anchors with `true` as `Headings::id()` takes them. Four
  baseline entries fixed and removed.
- The harness installs omnisense/contracts (its checkout under `OMNISENSE_CONTRACTS`, GitHub
  otherwise) as a development dependency, for the providers' usage policies, runs PHPStan
  (`phpstan`) on each package, names its entrypoint and runs on Docker's default bridge.
  omnisense/omnidocs itself does not require omnisense/contracts: nothing of its kernel fits a
  local model of a manual (its `Config` validates per source, its `Registry` holds sources, not
  providers; no source reaches a network), so the core stays as it is and each provider declares
  "no third party" as data.

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
