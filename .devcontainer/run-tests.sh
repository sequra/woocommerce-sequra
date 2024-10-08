#!/bin/bash
export XDEBUG_MODE=off
cd /var/www/html/wp-content/plugins/_sequra
vendor/bin/phpunit --configuration ./phpunit.xml.dist --testdox