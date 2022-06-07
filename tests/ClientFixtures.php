<?php

namespace FlagsmithTest;

use DoppioGancio\MockedClient\HandlerBuilder;
use DoppioGancio\MockedClient\MockedGuzzleClientBuilder;
use DoppioGancio\MockedClient\Route\RouteBuilder;
use Flagsmith\Engine\Environments\EnvironmentModel;
use Flagsmith\Flagsmith;
use Flagsmith\Utils\AnalyticsProcessor;
use GuzzleHttp\Psr7\Response;
use Http\Discovery\Psr17FactoryDiscovery;

class ClientFixtures
{
    public static function analyticsProcessor($client = null)
    {
        return new AnalyticsProcessor('api-key', 'http://host', 5, $client);
    }

    public static function getHandlerBuilder()
    {
        return new HandlerBuilder(
            Psr17FactoryDiscovery::findServerRequestFactory(),
        );
    }

    public static function getRouteBuilder()
    {
        return new RouteBuilder(
            Psr17FactoryDiscovery::findResponseFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );
    }

    public static function getMockClient($handlerBuilder = null, $addRoutes = true)
    {
        $handlerBuilder = $handlerBuilder ?? self::getHandlerBuilder();

        if ($addRoutes) {
            $rb = self::getRouteBuilder();
            // Route with Response
            $handlerBuilder->addRoute(
                $rb->new()
                ->withMethod('GET')
                ->withPath('/api/v1/environment-document/')
                ->withResponse(
                    new Response(
                        200,
                        [],
                        self::loadFileContents('environment.json')
                    )
                )
                ->build()
            );

            // Route with Response
            $handlerBuilder->addRoute(
                $rb->new()
                ->withMethod('POST')
                ->withPath('/api/v1/identities/')
                ->withResponse(new Response(200, [], self::loadFileContents('identities.json')))
                ->build()
            );

            // Route with Response
            $handlerBuilder->addRoute(
                $rb->new()
                ->withMethod('GET')
                ->withPath('/api/v1/flags/')
                ->withResponse(new Response(200, [], self::loadFileContents('flags.json')))
                ->build()
            );
        }

        $clientBuilder = new MockedGuzzleClientBuilder($handlerBuilder);

        return $clientBuilder->build();
    }

    private static function loadFileContents(string $file)
    {
        return file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . $file);
    }

    public static function localEvalFlagsmith()
    {
        $flagsmith = (new Flagsmith('ser.api_key', Flagsmith::DEFAULT_API_URL, null, 10))
            ->withClient(ClientFixtures::getMockClient());

        $flagsmith->updateEnvironment();
        yield $flagsmith;

        unset($flagsmith);
    }

    public static function getEnvironmentModel()
    {
        return EnvironmentModel::build(json_decode(self::loadFileContents('environment.json')));
    }

    public static function getFlags()
    {
        return EnvironmentModel::build(json_decode(self::loadFileContents('environment.json')));
    }
}
