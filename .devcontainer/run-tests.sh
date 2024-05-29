#!/bin/bash
export XDEBUG_MODE=off
cd /var/www/html/wp-content/plugins/sequra
vendor/bin/phpunit --configuration ./phpunit.xml.dist --testdox