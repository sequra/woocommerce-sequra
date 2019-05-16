#!/bin/bash
CURDIR=`pwd`
EXT="-dev"
cd $TMPDIR
rm -rf woocommerce-sequra
cp -R $CURDIR/../../woocommerce-sequra .
ls ./woocommerce-sequra/*
cd woocommerce-sequra/woocommerce-sequra
composer install --no-ansi --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader
cd ..
rm -rf $CURDIR/../dist/woocommerce-sequra$EXT.zip
zip -r9 $CURDIR/../dist/woocommerce-sequra$EXT.zip woocommerce-sequra -x@woocommerce-sequra/exclude.lst
rm rf $CURDIR/../dist/woocommerce-sequra-campaign$EXT.zip
zip -r9 $CURDIR/../dist/woocommerce-sequra-campaign$EXT.zip woocommerce-sequra-campaign -x@woocommerce-sequra-campaign/exclude.lst
cd $CURDIR