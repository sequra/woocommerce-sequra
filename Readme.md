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

Then, access to [plugin settings](http://localhost.sequrapi.com:8000/wp-admin/admin.php?page=wc-settings&tab=checkout&section=sequra) and login with user `admin` and password `admin`, or browse the [frontend](http://localhost.sequrapi.com:8000/?post_type=product)

### Customization

When the setup script runs, it takes the configuration from the ```.env``` file in the root of the repository. If the file doesn't exists, it will create a new one, copying the ```.env.sample``` template. In order to customize your environment before the setup occurs, you might create your ```.env``` file. To avoid errors, is important that you make a duplicate of ```.env.sample``` and then rename it to ```.env```

You can read the ```.env.sample``` file to know what are the available configuration variables and understand the purpose of each one.

### Stopping the environment

To stop the containers and perform the cleanup operations run:

```bash
./teardown.sh
```

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
| ```./bin/playwright``` | Run E2E in `sequra/tests-e2e` directory tests using Playwright using a Docker container |

If you require a composer dependency from a GitHub repository, you need to create a `auth.json` file in the root of the repository. Set this as the file content, replacing `GITHUB_TOKEN` with your access token:

```bash
{
    "github-oauth": {
        "github.com": "GITHUB_TOKEN"
    }
}
```

## seQura Helper plugin

This plugin is intended to provide helper functions to setup data or ease common development tasks.

### Configure for "dummy" merchant

This functionality set up the required data in the database to use the dummy merchant. To used it you must do a POST request to the webhook, like this (replace `<WP_URL>` with the value present in your `.env` file):

```bash
curl --location --request POST '<WP_URL>/?sq-webhook=dummy_config'
```

### Configure for "dummy_services" merchant

This functionality set up the required data in the database to use the dummy_services merchant. To used it you must do a POST request to the webhook, like this (replace `<WP_URL>` with the value present in your `.env` file):

```bash
curl --location --request POST '<WP_URL>/?sq-webhook=dummy_services_config'
```

## Unit and Integration Tests

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
## End to end Tests

You can use the provided utility `bin/playwright` to run E2E tests defined in `sequra/tests-e2e` directory. This utility will run tests in a headless mode inside of a Docker container of the official image provided by the Playwright team.

Also, you can pass additional arguments to the utility to configure test execution, like this:

 ```bash
 bin/playwright --shard=1/10 --project=chromium
 ```

Some examples of arguments you can append to the command above:

| Argument | Description |
| -------- | ------------------------------------------------------------------ |
| `--workers 4` | Runs 4 workers in parallel. Each worker will execute a test case. This is the default value. |
| `--debug` | Runs tests in debug mode |
| `--project=configuration-onboarding` | Execute an specific tests group. Options are defined in the `playwright.config.js` in the `projects` property. See the `name` property of each element of the array   |
| `./tests-e2e/example.spec.js` | Execute specific test file. Supports multiple file paths space separated. Also supports file name without extension and path like this: `example` |

More info at: https://playwright.dev/docs/intro

### Running using headed mode

It is possible to run Playwright in headed mode. This will open a browser window to execute the tests. For now, it is not possible by using the utility, so you need to install nvm on your local machine. Then, install npm (See system requirements at: https://playwright.dev/docs/intro#system-requirements).

Then, install browsers using this command:

```bash
npx playwright install
```

To run the tests in headed mode, run the following command in the repository root directory:

```bash
bin/playwright --headed
```

Note: append many arguments as needed to the command.

## Hidden pages

### Order status settings

Append the anchor `#settings-order_status` to the settings page to access the hidden configuration page, like this:

http://localhost.sequrapi.com:8000/wp-admin/options-general.php?page=sequra#settings-order_status