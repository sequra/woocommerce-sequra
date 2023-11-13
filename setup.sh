#!/bin/bash
docker run --rm --interactive --tty \
  --volume $PWD/sequra:/app \
  composer install
docker-compose up -d
docker-compose exec -u www-data web /bin/bash -c "/tmp/setup_woocommerce.sh"
