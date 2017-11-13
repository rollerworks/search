FROM jakzal/phpqa:alpine

RUN set -xe \
    && apk add --update openssl ca-certificates \
    && apk add --no-cache --virtual .php-deps \
        libpq \
        icu-libs \
        git \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        icu-dev  \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        bcmath \
        intl \
    && { find /usr/local/lib -type f -print0 | xargs -0r  --strip-all -p 2>/dev/null || true; } \
    && apk del .build-deps \
    && rm -rf /tmp/* /usr/local/lib/php/doc/* /var/cache/apk/*
