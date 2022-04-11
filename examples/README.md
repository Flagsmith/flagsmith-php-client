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

You can reduce network calls by using local evaluations.

```php
$flagsmith = (new Flagsmith(TOKEN));
// This will load the environment from cache (or API, if cache does not exist.)
$flagsmith->updateEnvironment();
```

 It is recommended to use a psr simple-cache implementation to cache the environment document between multiple requests.

```sh
composer require symfony/cache
```

```php
$flagsmith = (new Flagsmith(TOKEN))
  ->withCache(new Psr16Cache(new FilesystemAdapter()));
// Cache the environment call to reduce network calls for each and every evaluation.
// This will load the environment from cache (or API, if cache does not exist.)
$flagsmith->updateEnvironment();
```

An optional cron job can be added to refresh this cache at a set time depending on your choice. Please set EnvironmentTTL value for this purpose.

```php
// the environment will be cached for 100 seconds.
$flagsmith = $flagsmith->withEnvironmentTtl(100);
$flagsmith->updateEnvironment();
```

```sh
* * * 1 40 php index.php # using cli
* * * 1 40 curl http://localhost:8000/ # using http
```

Note:
- Please note that for the environment cache, please use the server key generated from the Flagsmith Settings menu. The key's prefix is `ser.`.
- The cache is important for concurrent requests. Without cache, each request in PHP is a different process with its own memory objects. The cache (filesystem or other) would enforce that the network call is reduced to a file system one.

## Troubleshooting
If you see dependency related issues. Try backing up and removing your composer.lock file and then running composer install
