#!/bin/bash
CURDIR=`pwd`
cd $TMPDIR
rm -rf woocommerce-sequra
git clone git@github.com:sequra/woocommerce-sequra.git
cd woocommerce-sequra/woocommerce-sequra
if [ "$1" != "" ]; then
	git checkout $1
	EXT="-$1"
fi
composer install --no-ansi --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader
cd ..
zip -r9 $CURDIR/../dist/woocommerce-sequra$EXT.zip woocommerce-sequra -x@woocommerce-sequra/exclude.lst
zip -r9 $CURDIR/../dist/woocommerce-sequra-campaign$EXT.zip woocommerce-sequra-campaign -x@woocommerce-sequra-campaign/exclude.lst
cd $CURDIR