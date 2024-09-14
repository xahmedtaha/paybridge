# PayBridge

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xahmedtaha/paybridge.svg?style=flat-square)](https://packagist.org/packages/xahmedtaha/paybridge)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/xahmedtaha/paybridge/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/xahmedtaha/paybridge/actions?query=workflow%3Arun-tests+branch%3Amain)

[//]: # ([![GitHub Code Style Action Status]&#40;https://img.shields.io/github/actions/workflow/status/xahmedtaha/paybridge/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square&#41;]&#40;https://github.com/xahmedtaha/paybridge/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain&#41;)
[![Total Downloads](https://img.shields.io/packagist/dt/xahmedtaha/paybridge.svg?style=flat-square)](https://packagist.org/packages/xahmedtaha/paybridge)

A versatile laravel package providing a consistent and easy-to-use interface for integrating with multiple payment gateways.
## Installation

You can install the package via composer:

```bash
composer require xahmedtaha/paybridge
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="paybridge-config"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$payBridge = new AhmedTaha\PayBridge();
echo $payBridge->echoPhrase('Hello, AhmedTaha!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Ahmed Taha](https://github.com/xahmedtaha)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
