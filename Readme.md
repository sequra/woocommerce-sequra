# seQura Payment Gateway for WooCommerce

This repository contains the plugin seQura Payment Gateway for WooCommerce.

## How to use

You can download the plugin from https://wordpress.org/plugins/sequra/ and use it on your own WooCommerce installation.

## Running with Docker 🐳

### Starting the environment

To setup and start the containers run:

```bash
./setup.sh
```
Optionally, you can pass the following parameters to the setup script:

| Argument | Description |
| -------- | ------------------------------------------------------------------ |
| ```--install=<0\|1>``` | Perform the installation of packages (1) or not (0). Default is 1 |

### Customization

When the setup script runs, it takes the configuration from the ```.env``` file in the root of the repository. If the file doesn't exists, it will create a new one, copying the ```.env.sample``` template. In order to customize your environment before the setup occurs, you might create your ```.env``` file. To avoid errors, is important that you make a duplicate of ```.env.sample``` and then rename it to ```.env```

You can read the ```.env.sample``` file to know what are the available configuration variables and understand the purpose of each one.

### Stopping the environment

To stop the containers and perform the cleanup operations run:

```bash
./teardown.sh
```

Then, access to [plugin settings](http://localhost.sequrapi.com:8000/wp-admin/admin.php?page=wc-settings&tab=checkout&section=sequra) and login with user `admin` and password `admin`, or browse the [frontend](http://localhost.sequrapi.com:8000/?post_type=product)

### Configuration

By default, the environment is set up with the latest versions of WordPress and MariaDB.
You might like to change this behavior in some scenarios (for example, to test with a different version of WordPress/PHP). 

For those cases, make a copy of the ```.env.sample``` file in the root directory of the repository, rename it to ```.env``` and customize the values according your needs.

## Utilities

This repo contains a group of utility scripts under ```bin/``` directory. The goal is to ease the execution of common tasks without installing additional software.

| Utility | Description |
| -------- | ------------------------------------------------------------------ |
| ```./bin/composer <arguments>``` | This is a wrapper to run composer commands |
| ```./bin/npm <arguments>``` | This is a wrapper to run npm commands |
| ```./bin/phpcs``` | Run PHP code sniffer on the project files |
| ```./bin/phpcbf``` | Automatically correct coding standard violations on the project files |
| ```./bin/phpstan``` | Run PHPStan on the project files |
| ```./bin/cp_sources``` | Copy WordPress Core and WooCommerce code to ```.devcontainer/``` |
| ```./bin/publish_to_wordpress.sh``` | Handles the plugin publishing to WordPress.org |

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
