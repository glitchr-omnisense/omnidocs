# The Docker harness

```sh
cd docker
docker compose run --rm omnidocs test     # every package's suite, BareTest included
docker compose run --rm omnidocs bare     # plain PHP: a manual, its pages, its search index; what PHP loaded
docker compose run --rm js                # assets/omnidocs.js on node's test runner
docker compose run --rm sphinx            # Requests' docs through Sphinx 8.1.3's JSON builder -> real/sphinx
docker compose run --rm doxygen           # TinyXML-2 through Doxygen -> real/doxygen
docker compose run --rm jsdoc             # flowjs through JSDoc 4.0.5 -> real/jsdoc.json
docker compose run --rm omnidocs real     # each provider's conformance suite on the whole of real/
docker compose down -v
```

The providers come from their checkouts beside the core (`OMNIDOCS_PLUGINS`, `../..` by default: the
workspace), from GitHub for those that are not there. The fixtures the providers' own suites read were
cut from the same tools' output: Fixtures/*/COMMIT names the project's commit.
