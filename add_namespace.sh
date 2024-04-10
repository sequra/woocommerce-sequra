# !/bin/sh
# This script is a helper to generate a unique namespace for each plugin dependency 
# in the composer.json file. This is useful when you have multiple plugins that 
# depend on the same library to avoid conflicts.
REPO_DIRECTORY="$(cd "$(dirname "$0")" && pwd)" # The directory where the script is located
PLUGIN_FORDER_IN_REPO="sequra"
VENDOR_BIN_DIRECTORY="vendor-bin"
BUILD_DIRECTORY="build"

echo "Starting to add namespaces to the plugin dependencies ⏳"

if ! cd $REPO_DIRECTORY/$PLUGIN_FORDER_IN_REPO ; then
    echo "Failed to enter directory $$REPO_DIRECTORY/$PLUGIN_FORDER_IN_REPO ❌"
    exit 1
fi

echo "Plugin directory set to: $REPO_DIRECTORY/$PLUGIN_FORDER_IN_REPO"

# Clean up the build and vendor-bin directories
rm -rf $BUILD_DIRECTORY $VENDOR_BIN_DIRECTORY

# Install packages...
composer config --no-plugins allow-plugins.bamarni/composer-bin-plugin true && \
composer require --dev bamarni/composer-bin-plugin && \
composer bin php-scoper require --dev humbug/php-scoper && \
composer install --no-dev --no-interaction

# Prefix PHP files...
vendor/bin/php-scoper add-prefix

# Remove installed packages...
composer remove --dev bamarni/composer-bin-plugin && \
composer remove --dev humbug/php-scoper

# Update the autoloader...
compose dump-autoload

rm -rf $VENDOR_BIN_DIRECTORY

if ! cd $BUILD_DIRECTORY ; then
    echo "Failed to enter directory $BUILD_DIRECTORY/ ❌"
    exit 1
fi

# For each PHP file in the build directory use sed to replace \false with false
for file in $(find . -name "*.php"); do
    sed -i '' 's/\\false/false/g' $file
    sed -i '' 's/\\true/true/g' $file
done

# Copy other files and directories...
cp -r $REPO_DIRECTORY/$PLUGIN_FORDER_IN_REPO/assets $REPO_DIRECTORY/$PLUGIN_FORDER_IN_REPO/$BUILD_DIRECTORY && \
cp -r $REPO_DIRECTORY/$PLUGIN_FORDER_IN_REPO/i18n $REPO_DIRECTORY/$PLUGIN_FORDER_IN_REPO/$BUILD_DIRECTORY && \
cp -r $REPO_DIRECTORY/$PLUGIN_FORDER_IN_REPO/templates $REPO_DIRECTORY/$PLUGIN_FORDER_IN_REPO/$BUILD_DIRECTORY


echo "Done ✅"