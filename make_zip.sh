#!/bin/bash
# Colors for the output
GREEN=$(tput setaf 2)
RED=$(tput setaf 1)
YELLOW=$(tput setaf 3)
WHITE=$(tput setaf 7)
NC=$(tput sgr0) # No color

BUILD_DIR="$(pwd)/zip" # Directory where the zip file will be created
GITHUB_REPO_OWNER="sequra" # GITHUB user who owns the repo
GITHUB_REPO_NAME="woocommerce-sequra" # GITHUB Repository name
PLUGIN_FORDER_IN_REPO="sequra" # Plugin folder in the repository
GIT_REPO="git@github.com:"${GITHUB_REPO_OWNER}"/"${GITHUB_REPO_NAME}".git" # GIT Repository URL

GIT_BRANCH=""
if [ "$1" != "" ]; then
	GIT_BRANCH="$1"
fi

echo "--------------------------------------------"
echo "      seQura WooCommerce Plugin ZIPPER      "
echo "--------------------------------------------"

# DELETE OLD TEMP DIRS
echo "Removing old directories $BUILD_DIR"
rm -Rf $BUILD_DIR

# Create the directory where the zip file will be created
mkdir -p $BUILD_DIR

cd $BUILD_DIR

# Clone the repository
echo "Cloning repository from GITHUB"
EXT=""
if [ "$GIT_BRANCH" != "" ]; then
    EXT=$(echo "-$GIT_BRANCH" | sed 's/\//_/g')
    git clone "$GIT_REPO" --branch "$GIT_BRANCH"
else
    git clone "$GIT_REPO"
fi

cd "$GITHUB_REPO_NAME/$PLUGIN_FORDER_IN_REPO"

# RUN COMPOSER
echo "Running composer"
docker run -it --rm -v "$(pwd)":/app -w /app composer:latest composer install --no-dev


# REMOVE UNWANTED FILES & FOLDERS
echo "Removing unwanted files"
rm -Rf .git
rm -Rf .github
rm -Rf .wordpress-org
rm -Rf tests
rm -Rf apigen
rm -f .gitattributes
rm -f .gitignore
rm -f .gitmodules
rm -f .travis.yml
rm -f Gruntfile.js
rm -f package.json
rm -f .jscrsrc
rm -f .jshintrc
rm -f .stylelintrc
rm -f composer.json
rm -f composer.lock
rm -f phpcs.xml
rm -f phpunit.xml
rm -f phpunit.xml.dist
rm -f README.md
rm -f .coveralls.yml
rm -f .editorconfig
rm -f .scrutinizer.yml
rm -f apigen.neon
rm -f CHANGELOG.txt
rm -f CONTRIBUTING.md
rm -f CODE_OF_CONDUCT.md
rm -f exclude.lst

# ZIP THE PLUGIN
echo "Zipping the plugin"
cd "$BUILD_DIR/$GITHUB_REPO_NAME"
zip -r9 "$BUILD_DIR/sequra$EXT.zip" "$PLUGIN_FORDER_IN_REPO"

# DELETE TEMP DIRS
echo "Removing temporary directories"
rm -Rf "$BUILD_DIR/$GITHUB_REPO_NAME"

echo "${GREEN}ZIP file created at: ${NC}$BUILD_DIR/sequra$EXT.zip"