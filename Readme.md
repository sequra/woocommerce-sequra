# seQura Payment Gateway for WooCommerce

### Description
This repository contains the plugin seQura Payment Gateway for WooCommerce.

### How to use

You can download the plugin from https://wordpress.org/plugins/sequra/ and use it on you own WooCommerce installation.

#### Local version

You could also run a local docker containing a WordPress with WooCommerce with the plugin installed.

Rename .env.test to .env if you want to customize any available option and then run

```bash
./setup.sh
```
and then open [plugin settings](http://localhost.sequrapi.com:8000/wp-admin/admin.php?page=wc-settings&tab=checkout&section=sequra) and login with user `admin` and password `admin`
or browse the [frontend](http://localhost.sequrapi.com:8000/?post_type=product)

#### Available customizations
* SQ_WORDPRESS_VERSION: (default:latest) WordPress version to use
* SQ_WORDPRESS_DATA_DIR: (default:sq_wordpress_data) Directory to store WordPress data
