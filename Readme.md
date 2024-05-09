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

> [!WARNING]  
> The ```docker-compose.yml``` file is created or deleted with those scripts. If you need to edit it, change the ```docker-compose-template.yml``` instead.

### Configuration

By default, the environment is set up with the latest versions of WordPress and MariaDB.
You might like to change this behavior in some scenarios (for example, to test with a different version of WordPress/PHP). 

For those cases, make a copy of the ```.env``` file in the root directory of the repository, rename it to ```override.env``` and customize the values. Leave only the variables with custom values in the file.