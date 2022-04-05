<img width="100%" src="https://github.com/Flagsmith/flagsmith/raw/main/static-files/hero.png"/>

# Flagsmith PHP Example

This example uses the following packages to invoke Flagsmith APIs.
- Nyholm/PSR7
- Symfony/HTTP-client
- PHP-HTTP/httplug
- Slim micro framework
- Slim-twig

## Steps
```sh
rm -rf ./vendor/
composer clear-cache
composer install
php index.php
php -S 0.0.0.0:8000
```

Returns a response of all the flags present.

More examples will be added for all methods and endpoints.

# Docker

The following steps can be used to run the files in a docker container.

Note: Please copy .env.sample as .env and replace the API key in the .env file.

```sh
docker-compose up -d
docker exec -it example-app sh -c "rm -rf ./vendor/"
docker exec -it example-app composer clear-cache
docker exec -it example-app composer install
docker exec -it example-app php -S 0.0.0.0:8000
```

# Reduce Flagsmith calls with local evaluation

You can reduce network calls by using local evaluations. It is recommended to use a psr simple-cache implementation to cache the environment document between multiple requests.

```php
$flagsmith = (new Flagsmith(TOKEN))
  ->withCache(new Psr16Cache(new FilesystemAdapter()));
// Cache the environment call to reduce network calls for each and every evaluation.
// This will load the environment from cache (or API, if cache does not exist.)
$flagsmith->updateEnvironment();
```

A cron job can be added to refresh this cache depending on your choice. Please set EnvironmentTTL value to match the cron refresh rate.

## Troubleshooting
If you see dependency related issues. Try backing up and removing your composer.lock file and then running composer install
