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
composer install
php index.php
```

Returns a response of all the flags present.

More examples will be added for all methods and endpoints.

# Docker

The following steps can be used to run the files in a docker container.

```sh
docker-compose up -d
docker exec -it example-app composer install
docker exec -it example-app php -S 0.0.0.0:80
```

## Troubleshooting
If you see dependency related issues. Try backing up and removing your composer.lock file and then running composer install
