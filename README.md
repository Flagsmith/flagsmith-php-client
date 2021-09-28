<img width="100%" src="https://github.com/Flagsmith/flagsmith/raw/main/static-files/hero.png"/>

# Flagsmith PHP SDK

> Flagsmith allows you to manage feature flags and remote config across multiple projects, environments and organisations.

The SDK for PHP applications for [https://www.flagsmith.com/](https://www.flagsmith.com/).

## Installation

`composer require flagsmith/flagsmith-php-client`

Requires PHP 7.4 or newer and ships with GuzzleHTTP.

You can optionally provide your own implementation of PSR-18 and PSR-16

> You will also need some implementation of [PSR-18](https://packagist.org/providers/psr/http-client-implementation)
> and [PSR-17](https://packagist.org/providers/psr/http-factory-implementation), for example
> [Guzzle](https://packagist.org/packages/guzzlehttp/guzzle)
> and [PSR-16](https://packagist.org/providers/psr/simple-cache-implementation), for example
> [Symfony Cache](https://packagist.org/packages/symfony/cache).
> Example:

`composer require flagsmith/flagsmith-php-client guzzlehttp/guzzle symfony/cache`

or

`composer require flagsmith/flagsmith-php-client symfony/http-client nyholm/psr7 symfony/cache`

## Usage

The Flagsmith PHP Client is utilized in such a way that makes it immutable. Everytime you change or set a setting the client will return a clone of itself.

```php
$flagsmith = new Flagsmith('apiToken');
```

## Adding to your project

For full documentation visit [https://docs.flagsmith.com/clients/php/](https://docs.flagsmith.com/clients/php/)

## Contributing

Please read [CONTRIBUTING.md](https://gist.github.com/kyle-ssg/c36a03aebe492e45cbd3eefb21cb0486) for details on our code of conduct, and the process for submitting pull requests

## Getting Help

If you encounter a bug or feature request we would like to hear about it. Before you submit an issue please search existing issues in order to prevent duplicates.

## Get in touch

If you have any questions about our projects you can email <a href="mailto:support@flagsmith.com">support@flagsmith.com</a>.

## Useful links

[Website](https://www.flagsmith.com/)

[Documentation](https://docs.flagsmith.com/)
