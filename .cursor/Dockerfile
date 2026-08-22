FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

# PHP 8.3 (satisfies composer.json "php": "^8.2") plus the extensions required by
# Composer, illuminate/support, and the orchestra/testbench dev dependency
# (mbstring, xml/dom, curl, zip, sqlite/pdo_sqlite, bcmath). git/curl/unzip are
# needed by Composer for VCS installs and archive extraction.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        php-cli \
        php-mbstring \
        php-xml \
        php-curl \
        php-zip \
        php-sqlite3 \
        php-bcmath \
        unzip \
        git \
        curl \
        ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install Composer 2 into a location on PATH.
RUN curl -sS https://getcomposer.org/installer | php -- \
        --install-dir=/usr/local/bin --filename=composer \
    && composer --version

USER ubuntu
