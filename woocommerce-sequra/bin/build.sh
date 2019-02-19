#!/bin/bash
CURDIR=`pwd`
cd $TMPDIR
rm -rf woocommerce-sequra
git clone git@github.com:sequra/woocommerce-sequra.git
cd woocommerce-sequra/woocommerce-sequra
composer install --no-ansi --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader
cd ..
zip -r9 $CURDIR/../dist/woocommerce-sequra.zip woocommerce-sequra -x@woocommerce-sequra/exclude.lst
zip -r9 $CURDIR/../dist/woocommerce-sequra-campaign.zip woocommerce-sequra-campaign -x@woocommerce-sequra-campaign/exclude.lst
cd $CURDIR