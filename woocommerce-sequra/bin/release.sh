export GITHUB_TOKEN=0eb99080e8c640a2a58308ec9312f1d1894a696a
TAG=4.8.0
NAME="New branding"
DESC="* New Branding
* WordPress Coding Standards"
git tag $TAG
if [ $? -eq 0 ]; then
    git push --tags
	github-release release \
		--user sequra \
		--repo woocommerce-sequra \
		--tag $TAG \
		--name "$NAME" \
		--description "$DESC" \
		--pre-release

	github-release upload \
		--user sequra \
		--repo woocommerce-sequra \
		--tag $TAG \
		--name "woocommerce-sequra.zip" \
		--file ../dist/woocommerce-sequra.zip
	github-release upload \
		--user sequra \
		--repo woocommerce-sequra \
		--tag $TAG \
		--name "woocommerce-sequra-campaign.zip" \
		--file ../dist/woocommerce-sequra-campaign.zip
else
    echo "Please, update release options before creating the release"
fi