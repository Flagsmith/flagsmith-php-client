<?php

use Flagsmith\Exceptions\FlagsmithClientError;
use Flagsmith\Flagsmith;
use Flagsmith\Models\DefaultFlag;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require_once './vendor/autoload.php';

$flagsmith = (new Flagsmith(getenv('API_KEY')))
    ->withDefaultFlagHandler(function ($featureName) {
        $defaultFlag = (new DefaultFlag())
            ->withEnabled(false)->withValue(null);
        if ($featureName === 'secret_button') {
            return $defaultFlag->withValue('{"colour": "#ababab"}');
        }

        return $defaultFlag;
    });

$featureName = 'secret_button';

// Create App
$app = AppFactory::create();

// Create Twig
$twig = Twig::create(__DIR__ . '/templates', ['cache' => false]);

// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response, $args) use ($flagsmith, $featureName) {
    $queryParams = $request->getQueryParams();
    $traits = (object)[];
    if (!empty($queryParams['traitname']) && !empty($queryParams['traitvalue'])) {
        extract($queryParams);
        $traits->{ $traitname } = $traitvalue;
    }

    $flags = $flagsmith->getIdentityFlags(($queryParams['identifier'] ? $queryParams['identifier'] : ''), $traits);

    $view = Twig::fromRequest($request);
    return $view->render($response, 'index.html', [
        'identifier' => $queryParams['identifier'],
        'traitname' => $queryParams['traitname'],
        'traitvalue' => $queryParams['traitvalue'],
        'font_colour' => json_decode($flags->getFeatureValue($featureName)),
        'enabled' => $flags->isFeatureEnabled($featureName)
    ]);
})->setName('profile');

// Run app
$app->run();
