# Softaculous VPS Addon for MyAdmin

[![Tests](https://github.com/detain/myadmin-softaculous-vps-addon/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-softaculous-vps-addon/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-softaculous-vps-addon/version)](https://packagist.org/packages/detain/myadmin-softaculous-vps-addon)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-softaculous-vps-addon/downloads)](https://packagist.org/packages/detain/myadmin-softaculous-vps-addon)
[![License](https://poser.pugx.org/detain/myadmin-softaculous-vps-addon/license)](https://packagist.org/packages/detain/myadmin-softaculous-vps-addon)

A MyAdmin plugin that provides Softaculous auto-installer license management as a VPS addon. This module integrates with the Softaculous NOC API to handle license provisioning, activation, and cancellation for VPS services.

## Features

- Automatic Softaculous license provisioning on VPS activation
- License cancellation on service disable with admin email notification
- Configurable addon cost through the MyAdmin settings interface
- Symfony EventDispatcher integration for hook-based architecture

## Requirements

- PHP 8.2 or higher
- ext-soap
- Symfony EventDispatcher 5.x, 6.x, or 7.x

## Installation

```sh
composer require detain/myadmin-softaculous-vps-addon
```

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

Licensed under the LGPL-2.1. See [LICENSE](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.en.html) for details.
