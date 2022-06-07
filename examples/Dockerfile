FROM php:7.4-cli

RUN apt-get update && \
  apt-get install -y software-properties-common

RUN apt-get update && apt-get install -y \
    git \
    zip \
    curl \
    sudo \
    unzip \
    libzip-dev \
    libicu-dev \
    libbz2-dev \
    libpng-dev \
    libjpeg-dev \
    libmcrypt-dev \
    libreadline-dev \
    libfreetype6-dev \
    libxml2-dev \
    libgmp-dev \
    g++

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN docker-php-ext-install bcmath

RUN docker-php-ext-install gmp

WORKDIR /var/code


CMD ["tail", "-f", "/dev/null"]