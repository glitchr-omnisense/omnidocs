#!/bin/sh
# Composes the harness (this core + every provider), installs it when it changed, then runs the
# command: "test" (the default) for PHPUnit, "bare" for the plain-PHP script, "real" for each
# provider's suite on the tools' whole output (real/), "phpstan" for PHPStan at level max on each package
# (its baseline included), "composer ...", "php ..." or "sh" as they are.
set -e
php /omnidocs/core/docker/harness/setup.php
cd /harness
if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --no-progress
elif [ composer.json -nt composer.lock ]; then
    composer update --no-interaction --no-progress
fi
case "${1:-test}" in
    test) shift || true; exec vendor/bin/phpunit "$@" ;;
    bare) shift; exec php /omnidocs/core/docker/harness/bin/bare /harness/vendor/autoload.php "$@" ;;
    real)
        shift || true
        OMNIDOCS_SPHINX_REAL=/omnidocs/real/sphinx/json OMNIDOCS_DOXYGEN_REAL=/omnidocs/real/doxygen/xml OMNIDOCS_JSDOC_REAL=/omnidocs/real/jsdoc.json \
            exec vendor/bin/phpunit --group real "$@" ;;
    phpstan)
        status=0
        for dir in /omnidocs/core /omnidocs/plugins/*; do
            [ -f "$dir/phpstan.neon" ] || continue
            [ "$dir" != /omnidocs/plugins/core ] || continue
            echo "== $dir"
            vendor/bin/phpstan analyse --no-progress --memory-limit=1G -c "$dir/phpstan.neon" --autoload-file=/harness/vendor/autoload.php "$dir" || status=1
        done
        exit $status ;;
    composer|sh|php) exec "$@" ;;
    *) echo "test, bare, real, phpstan, composer, sh or php" >&2; exit 2 ;;
esac
