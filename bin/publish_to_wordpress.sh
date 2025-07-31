#!/bin/sh

# Colors for the output
GREEN=$(tput setaf 2)
RED=$(tput setaf 1)
YELLOW=$(tput setaf 3)
WHITE=$(tput setaf 7)
CYAN=$(tput setaf 6)
NC=$(tput sgr0) # No color

PLUGIN_SLUG="sequra"
# GITHUB_REPO_OWNER="sequra"
# GITHUB_REPO_NAME="woocommerce-sequra"
PLUGIN_FOLDER_IN_REPO="/sequra"

ROOT_PATH=$TMPDIR
TEMP_GITHUB_REPO=${PLUGIN_SLUG}"-git"
TEMP_SVN_REPO=${PLUGIN_SLUG}"-svn"

SVN_REPO="http://plugins.svn.wordpress.org/"${PLUGIN_SLUG}"/"

read_input() {
	local message="$1" # Message to show to the user
	read -e -p "$message" input_value
	echo "$input_value"
}

validate_version() {
	local version=$1
	if [[ $version =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
		return 0
	else
		return 1
	fi
}

get_release_zip_url() {
	local url
	url=$(curl -s "https://api.github.com/repos/sequra/woocommerce-sequra/releases/tags/$1" \
	| grep 'browser_download_url' \
	| sed -E 's/.*"browser_download_url": "(.*)".*/\1/' \
	| grep -E "sequra-$1.*\.zip$" \
	| head -n 1)
	
	if [ -n "$url" ]; then
		echo "$url"
		return 0
	else
		return 1
	fi
}

set -e
clear

echo "${NC}--------------------------------"
echo "Github to WordPress.org RELEASER"
echo "--------------------------------"
VERSION=$(read_input "${YELLOW}Write the version to release: ${NC}")
until validate_version "$VERSION"; do
    VERSION=$(read_input "${RED}Invalid version format. Use X.Y.Z (e.g., 1.0.0): ${YELLOW}")
done

echo ""
echo "${NC}🔎 Fetching release $VERSION from GitHub..."
ZIP_URL=$(get_release_zip_url "$VERSION") || {
	echo "${RED}Error: Unable to fetch the release ZIP file URL for version $VERSION."
	exit 1
}
echo "${NC}✅ Found: $ZIP_URL"

echo ""

# Delete old temporary directories
cd $ROOT_PATH
echo "🗑️ Removing the temporary directories if they exist:"
rm -Rf $TEMP_GITHUB_REPO
echo "$ROOT_PATH$TEMP_GITHUB_REPO"
rm -Rf $TEMP_SVN_REPO
echo "$ROOT_PATH$TEMP_SVN_REPO"
mkdir -p $TEMP_GITHUB_REPO
mkdir -p $TEMP_SVN_REPO
echo ""

# Download the release ZIP file
echo "${NC}⬇️  Downloading the release ZIP file..."
cd $ROOT_PATH$TEMP_GITHUB_REPO
curl -L "$ZIP_URL" -o "sequra.zip" || {
	echo "${RED}Error: Unable to download the release ZIP file."
	exit 1
}
echo ""
# Unzip the downloaded file
echo "${NC}📦 Unzipping the release ZIP file..."
unzip -q "sequra.zip" || {
	echo "${RED}Error: Unable to unzip the release ZIP file."
	exit 1
}
rm "sequra.zip"
cd "$ROOT_PATH$TEMP_GITHUB_REPO$PLUGIN_FOLDER_IN_REPO"
echo ""

# Check the header version in sequra.php
VERSION_IN_FILE=$(grep "Version:" sequra.php | awk '{print $3}')
if [[ "$VERSION_IN_FILE" != "$VERSION" ]]; then
	echo "${RED}Error: The header version in sequra.php does not match the release version $VERSION."
	exit 1
fi
echo "${GREEN}✅ Header version in sequra.php matches the release version $VERSION.${NC}"

# Check if the readme.txt file has the correct stable tag
VERSION_IN_FILE=$(grep "Stable tag:" readme.txt | awk '{print $3}')
if [[ "$VERSION_IN_FILE" != "$VERSION" ]]; then
	echo "${RED}Error: The stable tag in readme.txt does not match the release version $VERSION."
	exit 1
fi
echo "${GREEN}✅ Stable tag in readme.txt matches the release version $VERSION.${NC}"

# Check if readme.txt has the changelog for the version
if ! grep -q "=\s*$VERSION\s*=" readme.txt; then
	echo "${RED}Error: The changelog for version $VERSION is not found in readme.txt."
	exit 1
fi
echo "${GREEN}✅ Changelog for version $VERSION found in readme.txt.${NC}"

# Ask for confirmation on the POT readiness
read -r -p "${YELLOW}Is the POT file updated? [y/N]: ${NC}" POT_READY
if [[ "$POT_READY" == "n" || "$POT_READY" == "N" || "$POT_READY" == "" ]]; then
	echo "${RED}Aborting: Please update the POT file before proceeding."
	exit 1
fi
echo "${GREEN}✅ POT file is up to date.${NC}"
echo ""

# Checkout and update the SVN repository
echo "${NC}⬇️  Getting the WordPress.org plugin repository..."
cd $ROOT_PATH
svn checkout "$SVN_REPO" "$TEMP_SVN_REPO" || {
	echo "${RED}Error: Unable to checkout repo $SVN_REPO"
	exit 1
}
cd "$TEMP_SVN_REPO"
svn update || { 
	echo "${RED}Error: Unable to update SVN."
	exit 1
}

# Check if the current version already exists in SVN
if svn ls "tags/$VERSION" > /dev/null 2>&1; then
	echo "${RED}Aborting: The version $VERSION already exists in WordPress.org."
	exit 1
fi

# Check if there is a tag greater than the current version
LATEST_TAG=$(ls tags | grep -Eo '[0-9]+\.[0-9]+\.[0-9]+' | sort -V | tail -n 1)
if [[ -n "$LATEST_TAG" && "$(printf '%s\n' "$VERSION" "$LATEST_TAG" | sort -V | tail -n 1)" != "$VERSION" ]]; then
	echo "${RED}Aborting: The version $VERSION is less than the latest tag $LATEST_TAG."
	exit 1
fi
echo ""

# Ask for confirmation to proceed with the release
read -r -p "${YELLOW}Ready to release version $VERSION to WordPress.org? [y/N]: ${NC}" CONTINUE
if [[ "$CONTINUE" == "n" || "$CONTINUE" == "N" || "$CONTINUE" == "" ]]; then
	echo "${RED}Aborting: Release cancelled."
	exit 1
fi
echo ""

# DELETE OLD TRUNK
echo "${NC}🗑️  Deleting old trunk..."
rm -rf trunk

# Copy the contents of the plugin folder to the SVN trunk
echo "${NC}📂 Copying plugin files to SVN trunk..."
cp -R $ROOT_PATH$TEMP_GITHUB_REPO$PLUGIN_FOLDER_IN_REPO trunk/

# DO THE ADD ALL NOT KNOWN FILES UNIX COMMAND
svn add --force * --auto-props --parents --depth infinity -q

# DO THE REMOVE ALL DELETED FILES UNIX COMMAND
MISSING_PATHS=$( svn status | sed -e '/^!/!d' -e 's/^!//' )
# iterate over filepaths
for MISSING_PATH in $MISSING_PATHS; do
    svn rm --force "$MISSING_PATH"
done

# COPY TRUNK TO TAGS/$VERSION
echo "${NC}📦 Copying trunk to new tag..."
svn copy trunk tags/${VERSION} || { 
	echo "${RED}Error: Unable to create tag."
	exit 1
}

# DO SVN COMMIT
clear
echo "${NC}📋 Showing SVN status"
svn status
echo ""

echo "⬆️  Committing to WordPress.org...this may take a while..."
svn --username sequradev commit -m "Release "${VERSION}", see readme.txt for the changelog." || { 
	echo "${RED}Error: Unable to commit."
	exit 1
}

echo ""
echo "${GREEN}✅ Release ${VERSION} successfully committed to WordPress.org!${NC}"
echo "Cleaning up temporary directories..."
# Clean up temporary directories
rm -rf $ROOT_PATH$TEMP_GITHUB_REPO
rm -rf $ROOT_PATH$TEMP_SVN_REPO
echo "${GREEN}✅ Temporary directories cleaned up.${NC}"
echo ""
echo "${GREEN}✅ DONE!${NC}"