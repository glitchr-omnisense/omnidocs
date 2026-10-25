# Changelog

Every version of omniscient/omnidocs, newest first ([Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
[Semantic Versioning](https://semver.org/)).

## Unreleased

### Added
- The model (Manual, Version, Node, Page, Rendered, Heading, Symbol), `SourceInterface`, the factories'
  `Registry` and `Config`, `LinkerInterface` and `RepositoryLinker`.
- `OnePage`: a version read as one page, its parts, its table of contents, its former addresses.
- `Search\SearchIndex`, `Html\Conventions`, `Html\Headings`, `Html\Links`, `Html\Text`, `Html\Prose`.
- `assets/omnidocs.js`, `assets/omnidocs.css`, `Bridge/Twig/templates`.
- `Testing\SourceContractTests`, `Testing\ArraySource`, `Testing\RecordingLinker`.
- The Docker harness: the packages' suites, the tools (Sphinx, Doxygen, JSDoc) on real projects.
