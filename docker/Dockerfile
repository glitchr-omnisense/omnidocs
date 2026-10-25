# PHP with Composer: the harness installs omnidocs and its providers from their checkouts at start.
FROM php:8.4-cli-alpine
RUN apk add --no-cache git unzip
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_CACHE_DIR=/harness/.cache
WORKDIR /harness
ENTRYPOINT ["/omnidocs/core/docker/entrypoint.sh"]
CMD ["test"]
