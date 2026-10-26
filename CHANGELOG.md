# Changelog

Every version of omnisense/omnidocs, newest first ([Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
[Semantic Versioning](https://semver.org/)).

## Unreleased

### Changed
- The package is `omnisense/omnidocs` (Omniscient is now Omnisense), its repository `glitchr-omnisense/omnidocs`; `replace` keeps `omniscient/omnidocs` resolving.

### Added
- The model (Manual, Version, Node, Page, Rendered, Heading, Symbol), `SourceInterface`, the factories'
  `Registry` and `Config`, `LinkerInterface` and `RepositoryLinker`.
- `OnePage`: a version read as one page, its parts, its table of contents, its former addresses.
- `Search\SearchIndex`, `Html\Conventions`, `Html\Headings`, `Html\Links`, `Html\Text`, `Html\Prose`.
- `assets/omnidocs.js`, `assets/omnidocs.css`, `Bridge/Twig/templates`.
- `Testing\SourceContractTests`, `Testing\ArraySource`, `Testing\RecordingLinker`.
- The Docker harness: the packages' suites, the tools (Sphinx, Doxygen, JSDoc) on real projects.
