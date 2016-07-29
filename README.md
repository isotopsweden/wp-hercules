# Hercules

> Requires PHP 5.5.9

Simple domain mapping.

## Installation

```
composer require isotopsweden/hercules
```

## Usage

Create `wp-content/sunrise.php`

```
<?php

// Default mu-plugins directory if you haven't set it.
defined( 'WPMU_PLUGIN_DIR' ) or define( 'WPMU_PLUGIN_DIR', WP_CONTENT_DIR . '/mu-plugins' );

require WPMU_PLUGIN_DIR . '/hercules/sunrise.php';
```

Additionally, in order for `sunrise.php` to be loaded, you must add the following to your `wp-config.php`:

```php
define( 'SUNRISE', true );
```

## License

MIT
