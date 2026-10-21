# Builds the SDK from source with the adapter for the Flagsmith SDK behavioural spec.
# Build from the repository root: docker build -f spec-adapter/Dockerfile .
FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libgmp-dev unzip \
    && docker-php-ext-install bcmath gmp \
    && rm -rf /var/lib/apt/lists/*
COPY --from=docker.io/library/composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /sdk
COPY composer.json ./
COPY src ./src
COPY spec-adapter/composer.json spec-adapter/adapter.php ./spec-adapter/
RUN composer install --working-dir=spec-adapter --no-dev --no-interaction --no-progress

ENV PORT=8080
EXPOSE 8080
CMD ["php", "spec-adapter/adapter.php"]
