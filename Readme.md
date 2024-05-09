# seQura Payment Gateway for WooCommerce

This repository contains the plugin seQura Payment Gateway for WooCommerce.

## How to use

You can download the plugin from https://wordpress.org/plugins/sequra/ and use it on your own WooCommerce installation.

## Running with Docker 🐳

To setup and start the containers run:

```bash
./setup.sh
```
To stop the containers and perform some cleanup operations run:

```bash
./terminate.sh
```

Then, access to [plugin settings](http://localhost.sequrapi.com:8000/wp-admin/admin.php?page=wc-settings&tab=checkout&section=sequra) and login with user `admin` and password `admin`, or browse the [frontend](http://localhost.sequrapi.com:8000/?post_type=product)

### Configuration

By default, the environment is set up with the latest versions of WordPress and MariaDB.
You might like to change this behavior in some scenarios (for example, to test with a different version of WordPress/PHP). 

For those cases, make a copy of the ```.env.sample``` file in the root directory of the repository, rename it to ```.env``` and customize the values according your needs.

## Tests

### Setup

Run following script to generate required files and initialize testing env:

```bash
docker compose exec web /usr/local/bin/setup-tests.sh
```
### Execution

```bash
docker compose exec web /usr/local/bin/run-tests.sh
```

### Running with VSCode

Install [PHPUnit Test Explorer](https://marketplace.visualstudio.com/items?itemName=recca0120.vscode-phpunit) extension.

Add this configuration to project workspace's settings:

```json
{
	"settings": {

        "phpunit.command": "docker compose exec web /bin/bash -c",
		"phpunit.php": "php",
		"phpunit.phpunit": "/var/www/html/wp-content/plugins/sequra/vendor/bin/phpunit",
		"phpunit.args": [
			"-c",
			"/var/www/html/wp-content/plugins/sequra/phpunit.xml.dist"
		],
		"phpunit.paths": {
			"${workspaceFolder}": "/var/www/html/wp-content/plugins",
		},
    }
}
```
