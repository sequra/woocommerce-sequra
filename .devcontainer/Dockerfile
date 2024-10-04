ARG WP_TAG=latest

FROM wordpress:${WP_TAG}

ENV WP_LOCALE="es_ES"
ENV WP_THEME="twentytwentyfour"
ENV WP_PLUGINS="woocommerce"

ENV WC_STORE_ADDRESS="No enviar"
ENV WC_STORE_ADDRESS_2="No enviar"
ENV WC_STORE_CITY="Barcelona"
ENV WC_STORE_POSTCODE="08001"
ENV WC_DEFAULT_COUNTRY="ES:B"
ENV WC_CURRENCY="EUR"
ENV WC_CURRENCY_POSITION="right_space"
ENV WC_PRICE_THOUSAND_SEPARATOR="."
ENV WC_PRICE_DECIMAL_SEPARATOR=","

ENV WC_SHIPPING_ZONE_METHOD_ID="flat_rate"
ENV WC_SHIPPING_ZONE_METHOD_SETTINGS='{"cost":"10"}'

SHELL ["/bin/bash", "-c"]

RUN pecl install xdebug && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=localhost.sequrapi.com" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.log=/dev/stdout" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.log_level=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN apt-get update && apt-get install -y \
    wget \
    zip \
    mariadb-client \
    subversion \
    netcat-openbsd && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x wp-cli.phar && \
    mv wp-cli.phar /usr/local/bin/wp

COPY ./sequra-entrypoint.sh /usr/local/bin/sequra-entrypoint.sh
COPY ./setup-tests.sh /usr/local/bin/setup-tests.sh
COPY ./run-tests.sh /usr/local/bin/run-tests.sh
RUN chmod +x /usr/local/bin/sequra-entrypoint.sh /usr/local/bin/setup-tests.sh /usr/local/bin/run-tests.sh
RUN sed -i 's/exec "$@"/\/usr\/local\/bin\/sequra-entrypoint.sh \&\& exec "$@"/g' /usr/local/bin/docker-entrypoint.sh
