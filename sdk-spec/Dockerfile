# Runs this SDK, built from source, under the Flagsmith SDK behavioural spec.
# docker build -f sdk-spec/Dockerfile --build-context harness=<sdk-spec>/harness/php .
FROM docker.io/library/php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libgmp-dev unzip \
    && docker-php-ext-install bcmath gmp \
    && rm -rf /var/lib/apt/lists/*
COPY --from=docker.io/library/composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /sdk
COPY composer.json ./
COPY src ./src
# Install the SDK with Guzzle as its PSR-18 HTTP client, as an application would.
RUN composer require guzzlehttp/guzzle:^7.3 --update-no-dev --optimize-autoloader --no-interaction --no-progress

COPY --from=harness harness.php /harness/harness.php
COPY sdk-spec/steps.php ./sdk-spec/steps.php

ENTRYPOINT ["php", "/harness/harness.php", "sdk-spec/steps.php"]
