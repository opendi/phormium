ARG COMPOSER_IMAGE=composer:2.0.13
ARG PHP_IMAGE=php:7.4.33-cli-alpine3.16

##
# build stage
##
FROM ${COMPOSER_IMAGE} AS composer-env

COPY . /var/opendi/phormium

WORKDIR /var/opendi/phormium

RUN set -xe \
    && composer install --ignore-platform-reqs

RUN set -xe \
    && composer dump-autoload --optimize

##
# final stage
##
FROM ${PHP_IMAGE} as final
ARG BUILD_ENV=production
WORKDIR /opt/phormium

COPY --from=composer-env /var/opendi/phormium/ /opt/phormium/

# execute local env helpers
COPY ./build/docker/local/php-cli/setup-local.sh setup-local.sh
RUN if [ "${BUILD_ENV}" = "local" ]; then /bin/sh setup-local.sh; fi
RUN rm -f setup-local.sh
