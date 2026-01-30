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

# Apache configuration
RUN a2enmod rewrite && echo "ServerName localhost" >> /etc/apache2/apache2.conf

COPY ./sequra-entrypoint.sh /usr/local/bin/sequra-entrypoint.sh
COPY ./setup-tests.sh /usr/local/bin/setup-tests.sh
COPY ./run-tests.sh /usr/local/bin/run-tests.sh
COPY ./toggle-xdebug.sh /usr/local/bin/toggle-xdebug
RUN chmod +x /usr/local/bin/sequra-entrypoint.sh /usr/local/bin/setup-tests.sh /usr/local/bin/run-tests.sh /usr/local/bin/toggle-xdebug

RUN pecl install xdebug && docker-php-ext-enable xdebug \
    && toggle-xdebug --mode=off \
    && echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/docker-php-woocommerce.ini \
    && echo "upload_max_filesize = 64M" >> /usr/local/etc/php/conf.d/docker-php-woocommerce.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/docker-php-woocommerce.ini

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

RUN sed -i 's/exec "$@"/\/usr\/local\/bin\/sequra-entrypoint.sh \&\& exec "$@"/g' /usr/local/bin/docker-entrypoint.sh
