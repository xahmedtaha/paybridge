# PayBridge

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xahmedtaha/paybridge.svg?style=flat-square)](https://packagist.org/packages/xahmedtaha/paybridge)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/xahmedtaha/paybridge/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/xahmedtaha/paybridge/actions?query=workflow%3Arun-tests+branch%3Amain)
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

## Usage

You should typically use the package as follows:

```php
use AhmedTaha\PayBridge\Data\ChargeData;use AhmedTaha\PayBridge\Data\CustomerData;use AhmedTaha\PayBridge\Data\Payment\CreditCardData;use AhmedTaha\PayBridge\Enums\PaymentEnvironment;use AhmedTaha\PayBridge\Enums\PaymentGateway;

$charge = new ChargeData('charge ID', 200, 'USD');
$customer = new CustomerData('customer ID', 'Ahmed', 'phone', 'email@test.com');
$paymentData = new CreditCardData('1234 1234 1234 1234', '24', '05', '123');

$result = PayBridge::setEnvironment(PaymentEnvironment::TESTING)
    ->gateway(PaymentGateway::FawryPay)
    ->pay($charge, $customer, $paymentData);

// $result = [
//  'success' => true,
//  'status' => PaymentStatus::PENDING,
//  'shouldRedirect' => true,
//  'redirectUrl' => 'fawry gateway url...',
//];
```

A callback url should be defined in the config files.
Here is how the code for this route should typically be:

```php
use PayBridge;
use AhmedTaha\PayBridge\Enums\PaymentGateway;
use AhmedTaha\PayBridge\Enums\PaymentEnvironment;
use Illuminate\Http\Request;

public function callbackRouteHandler(Request $request) {
    $result = PayBridge::setEnvironment(PaymentEnvironment::TESTING)
    ->gateway(PaymentGateway::FawryPay)
    ->callback($request);
    // Your additional app logic...
}

// $result = [
//  'success' => true,
//  'status' => PaymentStatus::PAID,
//  'charge' => ChargeData object,
//  'customer' => CustomerData object,
//  'referenceNumber' => 'fawry ref no',
//];
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
