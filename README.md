# Hercules [![Build Status](https://travis-ci.org/isotopsweden/wp-hercules.svg?branch=master)](https://travis-ci.org/isotopsweden/wp-hercules)

> Requires PHP 5.5.9 and WordPress 4.5

Simple domain mapping for top domains.

## Installation

```
composer require isotopsweden/wp-hercules
```

## Usage

Create `wp-content/sunrise.php`

```php
<?php

// Default mu-plugins directory if you haven't set it.
defined( 'WPMU_PLUGIN_DIR' ) or define( 'WPMU_PLUGIN_DIR', WP_CONTENT_DIR . '/mu-plugins' );

require WPMU_PLUGIN_DIR . '/wp-hercules/sunrise.php';
```

Additionally, in order for `sunrise.php` to be loaded, you must add the following to your `wp-config.php`:

```php
define( 'SUNRISE', 'on' );
```

## Create new site

With Hercules installed you should use [wp-cli](https://wp-cli.org) to add new site instead of `network/site-new.php`, this is because that Hercules modifies `wp site create` command so you can add a domain instead of just a slug, `SUBDOMAIN_INSTALL` should be set to `true`

```
wp site create --slug=example.com
```

## License

MIT © Isotop
