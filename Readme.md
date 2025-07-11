# seQura Payment Gateway for WooCommerce

This repository contains the plugin seQura Payment Gateway for WooCommerce.

## How to use

You can download the plugin from https://wordpress.org/plugins/sequra/ and use it on your own WooCommerce installation.

## For developers

### Customization

When the setup script runs, it takes the configuration from the `.env` file in the root of the repository. If the file doesn't exists, it will create a new one, copying the `.env.sample` template. In order to customize your environment before the setup occurs, you might create your `.env` file. To avoid errors, is important that you make a duplicate of `.env.sample` and then rename it to `.env`.

By default, the environment is set up with the latest supported versions of WordPress, WooCommerce and MariaDB.
You might like to change this behavior in some scenarios (for example, to test with a different version of WordPress/PHP). 

### Starting the environment

The repository includes a docker-compose file to easily test the module. You can start the environment with the following command:

```bash
./setup.sh
```
> [!IMPORTANT]
> Make sure you have the line `127.0.0.1	localhost.sequrapi.com` added in your hosts file.

> [!NOTE]
> Once the setup is complete, the WordPress root URL, wp-admin URL, and user credentials (including the password) will be displayed in your terminal.

Additionally, the setup script supports the following arguments:

| Argument | Description |
| -------- | ------------------------------------------------------------------ |
| `--install` | Install dependencies (composer and node) and generates required assets. |
| `--ngrok` |  Starts an ngrok container to expose the site to internet using HTTPS. An ngrok Auth Token must be provided either as an argument or as a variable in the `.env` file for it to work |
| `--ngrok-token=YOUR_NGROK_TOKEN` | Required to expose the environment to the internet. Get yours at https://dashboard.ngrok.com/ |

### Stopping the environment

To stop the containers and perform the cleanup operations run:

```bash
./teardown.sh
```

## Utilities

This repo contains a group of utility scripts under `bin/` directory. The goal is to ease the execution of common tasks without installing additional software.

| Utility | Description |
| -------- | ------------------------------------------------------------------ |
| `./bin/composer <arguments>` | This is a wrapper to run composer commands |
| `./bin/npm <arguments>` | This is a wrapper to run npm commands |
| `./bin/phpcs` | Run PHP code sniffer on the project files |
| `./bin/phpcbf` | Automatically correct coding standard violations on the project files |
| `./bin/phpstan` | Run PHPStan on the project files |
| `./bin/cp_sources` | Copy WordPress Core and WooCommerce code to `.devcontainer/` |
| `./bin/publish_to_wordpress.sh` | Handles the plugin publishing to WordPress.org |
| `./bin/make_zip` | Make a ZIP of `sequra` or a glue-plugin that is ready to be use for manual installations. The script allows the following arguments: `--branch=<GIT-BRANCH-NAME>` and `--project=<sequra\|sequra-no-address>`. The resulting file will be generated into `zip/` directory.|
| `./bin/playwright` | Run E2E in `sequra/tests-e2e` directory tests using Playwright using a Docker container |

If you require a composer dependency from a GitHub repository, you need to create a `auth.json` file in the root of the repository. Set this as the file content, replacing `GITHUB_TOKEN` with your access token:

```bash
{
    "github-oauth": {
        "github.com": "GITHUB_TOKEN"
    }
}
```
## Debugging

Debugging using XDebug is possible but you need to enable it first because is turned off by default in sake of performance. Use the following command to activate it:

```bash
docker compose exec web toggle-xdebug --mode=debug
```
Then, you need to configure VS Code to listen for XDebug connections. Add this configuration to project workspace's settings:

```json
{
	"settings": {
		"launch": {
			"version": "0.2.0",
			"configurations": [
				{
					"name": "Listen for Xdebug",
					"type": "php",
					"request": "launch",
					"port": 9003,
					"pathMappings": {
						"/var/www/html/wp-content/plugins/_sequra/": "${workspaceFolder}/sequra/",
						"/var/www/html/wp-content/plugins/sequra-helper/": "${workspaceFolder}/sequra-helper/",
						"/var/www/html/wp-content/plugins/sequra-no-address/": "${workspaceFolder}/glue-plugins/sequra-no-address",
					}
				},
			]
		},
	}
}
```
Note that you need the [PHP Debug](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug) extension active in your VS Code in order to debug.

To disable entirely XDebug, run the following command:

```bash
docker compose exec web toggle-xdebug --mode=off
```
## Using the profiler

XDebug includes a profiler that can be used to analyze the performance of the code. To enable the profiler, run the following command:

```bash
docker compose exec web toggle-xdebug --mode=profile
```
Each time a page loads in the browser, one ore more files will be generated at `/tmp/xdebug` directory inside the container. This path is mapped to the `.devcontainer/xdebug` directory in the host machine. You can use a tool like [QCacheGrind](https://sourceforge.net/projects/qcachegrind/) to analyze the generated files.

To install QCacheGrind in macOS you can use [Homebrew](https://brew.sh/):

```bash
brew install qcachegrind
```
Once installed, simply run `qcachegrind` and open the generated file.

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
### Clear plugin configuration

This functionality removes data in the database relative to plugin's configuration. To used it you must do a POST request to the webhook, like this (replace `<WP_URL>` with the value present in your `.env` file):

```bash
curl --location --request POST '<WP_URL>/?sq-webhook=clear_config'
```

### Force failure on seQura checkout

This functionality set up an scenario when the order has been modified increasing its amount values, after passing successfully the solicitation step and indeed will be rejected by seQura due the difference between solicited cart amount and the current cart amount to be paid. To used it you must do a POST request to the webhook, like this (replace `<WP_URL>` with the value present in your `.env` file and `<ID>` with the ID of the WC order):

```bash
curl --location --request POST '<WP_URL>/?sq-webhook=force_order_failure&order_id=<ID>'
```

### Clear the log

This functionality clears the plugin's log file by deleting it. To used it you must do a POST request to the webhook, like this (replace `<WP_URL>` with the value present in your `.env` file):

```bash
curl --location --request POST '<WP_URL>/?sq-webhook=remove_log'
```

### Fill the log with sample data

This functionality fill the plugin's log file with some entries, one for each severity level, using the same mechanism than the plugin. To used it you must do a POST request to the webhook, like this (replace `<WP_URL>` with the value present in your `.env` file):

```bash
curl --location --request POST '<WP_URL>/?sq-webhook=print_logs'
```

### Set active theme

This functionality set the active theme. To used it you must do a POST request to the webhook, like this (replace `<WP_URL>` and `<THEME>` with a value present in your `.env` file):

```bash
curl --location --request POST '<WP_URL>/?sq-webhook=set_theme&theme=<THEME>'
```

### Set cart page version

This functionality changes the Cart page content to use Classic or Block based layout. To used it you must do a POST request to the webhook, like this (replace `<WP_URL>` with the value present in your `.env` file and `<VERSION>` with `classic` or `blocks`):

```bash
curl --location --request POST '<WP_URL>/?sq-webhook=cart_version&version=<VERSION>'
```

### Set checkout page version

This functionality changes the Checkout page content to use Classic or Block based layout. To used it you must do a POST request to the webhook, like this (replace `<WP_URL>` with the value present in your `.env` file and `<VERSION>` with `classic` or `blocks`):

```bash
curl --location --request POST '<WP_URL>/?sq-webhook=checkout_version&version=<VERSION>'
```

### Get plugin zip file

This functionality prepares and returns a zipped version of the plugin, similar to the one you can get from wordpress.org. To used it you must do a POST request to the webhook, like this (replace `<WP_URL>` with the value present in your `.env` file):

```bash
curl --location --request POST '<WP_URL>/?sq-webhook=plugin_zip'
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
	    "phpunit.phpunit": "/var/www/html/wp-content/plugins/_sequra/vendor/bin/phpunit",
	    "phpunit.args": [
		    "-c",
		    "/var/www/html/wp-content/plugins/_sequra/phpunit.xml.dist"
	    ],
	    "phpunit.paths": {
	        "${workspaceFolder}": "/var/www/html/wp-content/plugins",
	    },
    }
}
```
## End to end Tests

### Installation

First, install NPM on your local machine (NVM is recommended) (See system requirements at: https://playwright.dev/docs/intro#system-requirements).

Then, Install required Node packages by running the following command from the root directory:

```bash
npx playwright install
```
Last, install browsers using this command:

```bash
npx playwright install
```

### Usage

You can use the provided utility `bin/playwright` to run E2E tests defined in `tests-e2e` directory. This utility will run tests in a headless mode inside of a Docker container of the official image provided by the Playwright team.

Also, you can pass additional arguments to the utility to configure test execution. Some examples of arguments you can append to the command above:

| Argument | Description |
| -------- | ------------------------------------------------------------------ |
| `--debug` | Runs tests in debug mode |
| `--project=configuration-onboarding` | Execute an specific tests group. Options are defined in the `playwright.config.js` in the `projects` property. See the `name` property of each element of the array   |
| `./tests-e2e/example.spec.js` | Execute specific test file. Supports multiple file paths space separated. Also supports file name without extension and path like this: `example` |

More info at: https://playwright.dev/docs/intro

> [!IMPORTANT]
> In order for some tests to succeed, you must expose your Magento container to the internet, so that the callbacks made by SeQura can work. Make sure that you run the setup script passing the `--ngrok` argument.

> [!IMPORTANT]
> Make sure you wrote values for `DUMMY_PASSWORD`, `DUMMY_SERVICE_PASSWORD` and `DUMMY_ASSETS_KEY` in the `.env` file before launching e2e tests.

### Running using UI mode

> [!NOTE]  
> This is the recommended way to execute the E2E tests.

UI Mode lets you explore, run, and debug tests with a time travel experience complete with a watch mode. All test files are displayed in the testing sidebar, allowing you to expand each file and describe block to individually run, view, watch, and debug each test.

Run the following command in the repository root directory:

```bash
bin/playwright --ui
```

### Running using headed mode

It's also possible to run Playwright in headed mode. This will open a browser window to execute the tests. This approach could help you when you some debugging is needed. Run the following command in the repository root directory:

```bash
bin/playwright --headed
```

Note: append many arguments as needed to the command. For example `--debug`.

## i18n automation

The following command can be used to extract the strings from the plugin to the `.pot` file:

```bash
docker compose exec -u www-data web wp loco extract sequra
```
This guarantees that the `.pot` file is always up to date with the plugin strings and should be run on every release branch before merging it to the main branch.

## Hidden pages

### Order status settings

Append the anchor `#settings-order_status` to the settings page to access the hidden configuration page, like this:

http://localhost.sequrapi.com:8000/wp-admin/options-general.php?page=sequra#settings-order_status