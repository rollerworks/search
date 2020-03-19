FROM php:7.2-cli

ENV BUILD_DEPS="autoconf file g++ gcc libc-dev pkg-config re2c"
ENV LIB_DEPS="git make unzip ca-certificates zlib1g-dev libpq5 postgresql-server-dev-11"
ENV ICU_RELEASE=60.1
ENV CXXFLAGS "--std=c++0x"

RUN apt-get update && apt-get install -y --no-install-recommends $BUILD_DEPS $LIB_DEPS && rm -rf /var/lib/apt/lists/*
RUN cd /tmp && curl -Ls http://download.icu-project.org/files/icu4c/$ICU_RELEASE/icu4c-$(echo $ICU_RELEASE | tr '.' '_')-src.tgz > icu4c-src.tgz \
 && cd /tmp && tar xzf icu4c-src.tgz && cd /tmp/icu/source && ./configure && make && make install && rm -rf /tmp/icu /tmp/icu4c-src.tgz

RUN echo "date.timezone=Europe/Amsterdam" >> $PHP_INI_DIR/php.ini \
 && echo "memory_limit=-1" >> $PHP_INI_DIR/php.ini \
 && echo "phar.readonly=0" >> $PHP_INI_DIR/php.ini \
 && docker-php-ext-install zip pdo_pgsql pgsql bcmath mysqli pdo_mysql \
 && docker-php-ext-configure intl && docker-php-ext-install intl \
 && apt-get purge -y --auto-remove $BUILD_DEPS

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN ln -s /usr/bin/composer /usr/bin/composer.phar

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER 1
# install Symfony Flex globally to speed up download of Composer packages (parallelized prefetching)
RUN set -eux; \
	composer global require "symfony/flex" --prefer-dist --no-progress --no-suggest --classmap-authoritative; \
	composer clear-cache
ENV PATH="${PATH}:/root/.composer/vendor/bin"

WORKDIR /srv/www

CMD icu-config --version && php -i | grep 'ICU version'
