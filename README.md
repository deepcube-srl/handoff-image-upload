# This is my package handoff-image-upload

[![Latest Version on Packagist](https://img.shields.io/packagist/v/deepcube/handoff-image-upload.svg?style=flat-square)](https://packagist.org/packages/deepcube/handoff-image-upload)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/deepcube/handoff-image-upload/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/deepcube/handoff-image-upload/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/deepcube/handoff-image-upload/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/deepcube/handoff-image-upload/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/deepcube/handoff-image-upload.svg?style=flat-square)](https://packagist.org/packages/deepcube/handoff-image-upload)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require deepcube/handoff-image-upload
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="handoff-image-upload-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="handoff-image-upload-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="handoff-image-upload-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$handoffImageUpload = new Deepcube\HandoffImageUpload();
echo $handoffImageUpload->echoPhrase('Hello, Deepcube!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Deepcube Srl](https://github.com/deepcube)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
