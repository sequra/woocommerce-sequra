ARG WORDPRESS_VERSION
FROM wordpress:${WORDPRESS_VERSION:-latest}
SHELL ["/bin/bash", "-c"]
ENV XDEBUG_PORT 9003
ENV XDEBUG_IDEKEY docker

COPY ./setup_woocommerce.sh /tmp/setup_woocommerce.sh
COPY ./launch.json /var/www/html/.vscode/launch.json
# [Optional] Uncomment this section to install additional OS packages.
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends sudo

RUN pecl install "xdebug" \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && docker-php-ext-enable xdebug \
	&& echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.client_port=${XDEBUG_PORT}" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.idekey=${XDEBUG_IDEKEY}" >> /usr/local/etc/php/conf.d/xdebug.ini \
	&& curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp \
	&& mkdir /var/www/.wp-cli \
	&& chown www-data:www-data /var/www/.wp-cli

# # [Choice] Node.js version: none, lts/*, 16, 14, 12, 10
# ARG NODE_VERSION="19"
# ENV NVM_DIR /usr/local/nvm

# # install nvm
# # https://github.com/creationix/nvm#install-script
# RUN mkdir -p $NVM_DIR && curl --silent -o- https://raw.githubusercontent.com/creationix/nvm/v0.39.3/install.sh | bash

# # install node and npm
# RUN source $NVM_DIR/nvm.sh \
#     && nvm install $NODE_VERSION \
#     && nvm alias default $NODE_VERSION \
#     && nvm use default

# RUN if [ "${NODE_VERSION}" != "none" ]; then umask 0002 && . $NVM_DIR/nvm.sh && nvm install ${NODE_VERSION} 2>&1; fi



# # [Optional] Uncomment this line to install global node packages.
# # RUN su vscode -c "source /usr/local/share/nvm/nvm.sh && npm install -g <your-package-here>" 2>&1

# RUN yes | npx playwright install-deps  
# RUN npx playwright install
