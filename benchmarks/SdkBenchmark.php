<?php

namespace FlagsmithBenchmark;

use Flagsmith\Flagsmith;
use Flagsmith\Models\Segment;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Benchmarks a locally evaluating SDK client against environment documents tiered by size.
 */
class SdkBenchmark
{
    public const ENVIRONMENTS = ['small', 'medium', 'large'];
    public const BENCHMARKS = [
        'getEnvironmentFlags',
        'getIdentityFlagsWithIdentityOverrides',
        'getIdentityFlagsNoTraits',
        'getIdentityFlagsWithTraits',
        'getIdentitySegmentsWithTraits',
    ];

    private const IDENTITY_OVERRIDES_IDENTIFIER = 'identity-override-1';
    private const IDENTIFIER = 'user-1';
    private const TRAITS = [
        'plan' => 'plan-7',
        'age' => 30,
        'country' => 'GB',
        'version' => '2.5.1',
        'email' => 'jane@example.com',
        'user_id' => 12,
        'beta' => true,
        'score' => 95,
        'tier' => 'pro',
        'device' => 'iPhone 15',
    ];

    private Flagsmith $client;

    /**
     * Build a locally evaluating client serving the environment document from memory.
     */
    public function __construct(string $environmentsDir, string $environment)
    {
        $environmentDocument = file_get_contents("{$environmentsDir}/{$environment}.json");

        $this->client = (new Flagsmith(
            apiKey: 'ser.benchmark',
            host: 'http://flagsmith.benchmark/api/v1/',
            environmentTtl: 86400,  // Refreshed by benchmarks only
        ))->withClient(new Client([
            'handler' => static fn (RequestInterface $request): PromiseInterface => Create::promiseFor(
                new Response(200, ['Content-Type' => 'application/json'], $environmentDocument),
            ),
        ]));
        $this->client->updateEnvironment();
    }

    /**
     * Get a feature value from the environment flags.
     */
    public function getEnvironmentFlags(): mixed
    {
        return $this->client->getEnvironmentFlags()->getFeatureValue('feature_1');
    }

    /**
     * Check a feature for an identity with identity overrides.
     */
    public function getIdentityFlagsWithIdentityOverrides(): bool
    {
        return $this->client->getIdentityFlags(self::IDENTITY_OVERRIDES_IDENTIFIER)->isFeatureEnabled('feature_8');
    }

    /**
     * Get a feature value for an identity without traits.
     */
    public function getIdentityFlagsNoTraits(): mixed
    {
        return $this->client->getIdentityFlags(self::IDENTIFIER)->getFeatureValue('feature_1');
    }

    /**
     * Get a feature value for an identity with traits.
     */
    public function getIdentityFlagsWithTraits(): mixed
    {
        return $this->client->getIdentityFlags(self::IDENTIFIER, (object) self::TRAITS)->getFeatureValue('feature_1');
    }

    /**
     * List the segments of an identity with traits.
     * @return array<Segment>
     */
    public function getIdentitySegmentsWithTraits(): array
    {
        return $this->client->getIdentitySegments(self::IDENTIFIER, (object) self::TRAITS);
    }
}
