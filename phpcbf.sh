#!/bin/bash
docker compose exec web bash -c 'export XDEBUG_MODE=off \
&& php wp-content/plugins/sequra/vendor/bin/phpcbf --standard=wp-content/plugins/sequra/.phpcs.xml.dist wp-content/plugins/sequra \
&& export XDEBUG_MODE=on'