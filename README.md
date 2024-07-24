<img width="100%" src="https://github.com/Flagsmith/flagsmith/raw/main/static-files/hero.png"/>

[![Packagist Version](https://img.shields.io/packagist/v/flagsmith/flagsmith-php-client)](https://packagist.org/packages/flagsmith/flagsmith-php-client)
[![Packagist Downloads](https://img.shields.io/packagist/dm/flagsmith/flagsmith-php-client)](https://packagist.org/packages/flagsmith/flagsmith-php-client)

# Flagsmith PHP SDK

> Flagsmith allows you to manage feature flags and remote config across multiple projects, environments and organisations.

The SDK for PHP applications for [https://www.flagsmith.com/](https://www.flagsmith.com/).

## Requirements

The Flagsmith PHP SDK requires the following PHP extensions to be enabled. These are essentials for the library to function properly.

- bc-math
- gmp
- json

Please view the documentation here to install the extensions, if you haven't already. For [BC-Math](https://www.php.net/manual/en/bc.installation.php) and [GMP](https://www.php.net/manual/en/gmp.installation.php).

## Local Evaluation

Since PHP requests are separate, there is little benefit to use local evaluation without caching. To enable local evaluation, please set the environmentTtl value (>0) and using PSR simple cache implementation.

## Adding to your project

For full documentation visit [https://docs.flagsmith.com/clients/server-side](https://docs.flagsmith.com/clients/server-side).

## Contributing

Please read [CONTRIBUTING.md](https://gist.github.com/kyle-ssg/c36a03aebe492e45cbd3eefb21cb0486) for details on our code of conduct, and the process for submitting pull requests

## Getting Help

If you encounter a bug or feature request we would like to hear about it. Before you submit an issue please search existing issues in order to prevent duplicates.

## Get in touch

If you have any questions about our projects you can email <a href="mailto:support@flagsmith.com">support@flagsmith.com</a>.

## Useful links

[Website](https://www.flagsmith.com/)

[Documentation](https://docs.flagsmith.com/)
