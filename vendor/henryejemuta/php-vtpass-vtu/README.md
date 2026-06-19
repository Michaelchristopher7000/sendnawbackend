# PHP VTpass VTU Client

[![Run Tests](https://github.com/henryejemuta/php-vtpass-vtu/actions/workflows/run-tests.yml/badge.svg)](https://github.com/henryejemuta/php-vtpass-vtu/actions/workflows/run-tests.yml)
[![Latest Stable Version](https://poser.pugx.org/henryejemuta/php-vtpass-vtu/v/stable)](https://packagist.org/packages/henryejemuta/php-vtpass-vtu)
[![Total Downloads](https://poser.pugx.org/henryejemuta/php-vtpass-vtu/downloads)](https://packagist.org/packages/henryejemuta/php-vtpass-vtu)
[![License](https://poser.pugx.org/henryejemuta/php-vtpass-vtu/license)](https://packagist.org/packages/henryejemuta/php-vtpass-vtu)
[![Quality Score](https://img.shields.io/scrutinizer/g/henryejemuta/php-vtpass-vtu.svg?style=flat-square)](https://scrutinizer-ci.com/g/henryejemuta/php-vtpass-vtu)

A PHP package for integrating with the [VTpass API](https://www.vtpass.com/documentation/). This package allows you to easily purchase airtime, data, electricity, and other services provided by VTpass.

## Whitelist Products

Although you have access to all VTpass products, they are **disabled by default**. To start selling specific products, you must **whitelist** them first. Whitelisting allows you to activate only the products you intend to offer through your integration. Follow the steps below to whitelist your desired products and enable them for vending.

#### Steps on how to whitelist products:

- To whitelist products for:
  1.  The live environment, go to your [VTpass profile here](https://vtpass.com/account).
  2.  The Sandbox environment, go to your [Sandbox profile here](https://sandbox.vtpass.com/account).
- Click on the Product Settings tab on your profile page.
- Check the products you would like to vend and click the submit button

## Request API Access

Now that you have gone through the documentation and all necessary tests done on the test environment, you are almost ready to go live!

1.  **Create Live Account**: The next step is to create a live account [here](https://www.vtpass.com/).
2.  **Request Access**: After creating a live account, you need to request API access by clicking [here](https://www.vtpass.com/request-api-access) (you need to be logged in to your live account before you can access this).
3.  **Fill Form**: Fill the form on the page and check the service(s) integrated. **Note:** You will be required to input the request ID of a successful integration.
4.  **Submit**: Once done, click on submit.

## Installation

You can install the package via composer:

```bash
composer require henryejemuta/php-vtpass-vtu
```

## Usage

```php
use HenryEjemuta\Vtpass\Client;

// Initialize the client
// For Live Environment
$client = new Client('your-api-key', 'your-public-key', 'your-secret-key');

// For Sandbox Environment
$client = new Client('your-api-key', 'your-public-key', 'your-secret-key', [
    'sandbox' => true
]);

// Get Service Categories
$categories = $client->getServiceCategories();

// Purchase Airtime
$response = $client->purchaseAirtime('mtn', 100, '08012345678');

// Purchase Data
$response = $client->purchaseData('mtn-data', '08012345678', 'sme-month-1GB', 300);
```

## Documentation

For full API documentation, please visit the [VTpass API Documentation](https://www.vtpass.com/documentation/).

## Testing

```bash
composer test
```
