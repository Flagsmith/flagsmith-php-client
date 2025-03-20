<?php

namespace Flagsmith;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Engine\Engine;
use Flagsmith\Engine\Environments\EnvironmentModel;
use Flagsmith\Engine\Identities\IdentityModel;
use Flagsmith\Engine\Identities\Traits\TraitModel;
use Flagsmith\Engine\Segments\SegmentEvaluator;
use Flagsmith\Engine\Utils\Collections\FeatureStateModelList;
use Flagsmith\Engine\Utils\Collections\IdentityTraitList;
use Flagsmith\Exceptions\FlagsmithAPIError;
use Flagsmith\Exceptions\FlagsmithClientError;
use Flagsmith\Exceptions\FlagsmithThrowable;
use Flagsmith\Models\Flags;
use Flagsmith\Models\Segment;
use Flagsmith\Offline\IOfflineHandler;
use Flagsmith\Utils\AnalyticsProcessor;
use Flagsmith\Utils\IdentitiesGenerator;
use Flagsmith\Utils\Retry;
use JsonException;
use ValueError;
use Psr\Http\Client\ClientInterface;
use Psr\SimpleCache\CacheInterface;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Flagsmith
{
    use HasWith;
    public const DEFAULT_API_URL = 'https://edge.api.flagsmith.com/api/v1';
    private ?string $apiKey;
    private ?string $host;
    private ?object $customHeaders = null;
    private ?int $environmentTtl = null;
    private bool $enableLocalEvaluation = false;
    private Retry $retries;
    private ?AnalyticsProcessor $analyticsProcessor = null;
    private ?\Closure $defaultFlagHandler = null;
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private string $environment_flags_url = 'flags/';
    private string $identities_url = 'identities/';
    private string $environment_url = 'environment-document/';
    private ?Cache $cache = null;
    private ?int $timeToLive = null;
    private string $cachePrefix = 'flagsmith';
    private bool $skipCache = false;
    private bool $useCacheAsFailover = false;
    private array $headers = [];
    private ?EnvironmentModel $environment = null;
    private bool $offlineMode = false;
    private ?IOfflineHandler $offlineHandler = null;

    /**
     * @throws ValueError
     */
    public function __construct(
        ?string $apiKey = null,
        ?string $host = null,
        ?object $customHeaders = null,
        ?int $environmentTtl = null,
        ?Retry $retries = null,
        bool $enableAnalytics = false,
        ?\Closure $defaultFlagHandler = null,
        bool $offlineMode = false,
        ?IOfflineHandler $offlineHandler = null
    ) {
        if ($offlineMode and is_null($offlineHandler)) {
            throw new ValueError('offlineHandler must be provided to use offline mode.');
        }

        if (!is_null($offlineHandler) and !is_null($defaultFlagHandler)) {
            throw new ValueError('Cannot use both defaultFlagHandler and offlineHandler.');
        }

        if (is_int($environmentTtl)) {
            if (stripos($apiKey, 'ser.') === false) {
                throw new ValueError(
                    'In order to use local evaluation, please generate a server key in the environment settings page.'
                );
            }
        }

        $this->offlineMode = $offlineMode;
        $this->offlineHandler = $offlineHandler;

        if (!is_null($offlineHandler)) {
            $this->environment = $offlineHandler->getEnvironment();
        }

        if (!$offlineMode) {
            if (is_null($apiKey)) {
                throw new ValueError('apiKey is required.');
            }

            $this->apiKey = $apiKey;
            $this->host = !is_null($host) ? rtrim($host, '/') : self::DEFAULT_API_URL;
            $this->customHeaders = $customHeaders ?? $this->customHeaders;
            $this->environmentTtl = $environmentTtl ?? $this->environmentTtl;
            $this->enableLocalEvaluation = !is_null($environmentTtl);
            $this->retries = $retries ?? new Retry(3);
            $this->analyticsProcessor = $enableAnalytics ? new AnalyticsProcessor($apiKey, $host) : null;
            $this->defaultFlagHandler = $defaultFlagHandler ?? $this->defaultFlagHandler;

            //We default to using Guzzle for the HTTP client (as this is how it worked in 1.0)
            $this->client = Psr18ClientDiscovery::find();
            $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
            $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        }
    }

    /**
     * Build with customer headers.
     * @param object $customHeaders
     * @return Flagsmith
     */
    public function withCustomHeaders(object $customHeaders): self
    {
        return $this->with('customHeaders', $customHeaders);
    }

    /**
     * Build with Retry object.
     * @param Retry $retries
     * @return Flagsmith
     */
    public function withRetries(Retry $retries): self
    {
        return $this->with('retries', $retries);
    }

    /**
     * Build with Environment cache TTL.
     * @param int $environmentTtl
     * @return Flagsmith
     */
    public function withEnvironmentTtl(int $environmentTtl): self
    {
        return $this->with('environmentTtl', $environmentTtl);
    }

    /**
     * Build with enable Analytics.
     * @param bool $enableAnalytics
     * @return Flagsmith
     */
    public function withAnalytics(AnalyticsProcessor $analytics): self
    {
        return $this->with('analyticsProcessor', $analytics);
    }

    /**
     * Build with default flag handler.
     * @param \Closure $defaultFlagHandler
     * @return Flagsmith
     */
    public function withDefaultFlagHandler(\Closure $defaultFlagHandler): self
    {
        return $this->with('defaultFlagHandler', $defaultFlagHandler);
    }

    /**
     * Set the Client
     *
     * @param ClientInterface $client
     * @return self
     */
    public function withClient(ClientInterface $client): self
    {
        return $this->with('client', $client);
    }

    /**
     * Set the Request Factory
     *
     * @param RequestFactoryInterface $requestFactory
     * @return self
     */
    public function withRequestFactory(
        RequestFactoryInterface $requestFactory
    ): self {
        return $this->with('requestFactory', $requestFactory);
    }

    /**
     * Set the Stream Factory
     *
     * @param StreamFactoryInterface $streamFactory
     * @return self
     */
    public function withStreamFactory(
        StreamFactoryInterface $streamFactory
    ): self {
        return $this->with('streamFactory', $streamFactory);
    }

    /**
     * Set the cache
     *
     * @param CacheInterface|null $cache
     * @return self
     */
    public function withCache(?CacheInterface $cache): self
    {
        if (is_null($cache)) {
            $this->with('cache', null);
        }

        return $this->with(
            'cache',
            new Cache($cache, $this->cachePrefix, $this->timeToLive)
        );
    }

    /**
     * Check if Cache is set
     *
     * @return boolean
     */
    public function hasCache(): bool
    {
        return !is_null($this->cache);
    }

    /**
     * Get the Cache Wrapper
     *
     * @return Cache|null
     */
    public function getCache(): ?Cache
    {
        return $this->cache;
    }

    /**
     * Set Cache Time To Live
     *
     * @param integer|null $timeToLive
     * @return self
     */
    public function withTimeToLive(?int $timeToLive = null): self
    {
        return $this->with('timeToLive', $timeToLive);
    }

    /**
     * Set the value of cachePrefix
     *
     * @param string $cachePrefix
     *
     * @return self
     */
    public function withCachePrefix(string $cachePrefix): self
    {
        return $this->with('cachePrefix', $cachePrefix);
    }

    /**
     * Should the cache be skipped (but still updated)
     *
     * @return bool
     */
    public function skipCache(): bool
    {
        return $this->skipCache;
    }

    /**
     * Whether to Skip Cache (but still update it)
     *
     * @param bool $skipCache
     *
     * @return self
     */
    public function withSkipCache(bool $skipCache): self
    {
        return $this->with('skipCache', $skipCache);
    }

    /**
     * Whether to use the Cache as a failover for server errors
     *
     * @param bool $useCacheAsFailover
     *
     * @return self
     */
    public function withUseCacheAsFailover(bool $useCacheAsFailover): self
    {
        return $this->with('useCacheAsFailover', $useCacheAsFailover);
    }

    /**
     * Get the environment model.
     * @return EnvironmentModel|null
     */
    public function getEnvironment(): ?EnvironmentModel
    {
        return $this->environment;
    }

    /**
     * Get all the default for flags for the current environment.
     * @return Flags
     *
     * @throws FlagsmithThrowable
     */
    public function getEnvironmentFlags(): Flags
    {
        if (($this->offlineMode || $this->enableLocalEvaluation) && $this->environment) {
            return $this->getEnvironmentFlagsFromDocument();
        }

        return $this->getEnvironmentFlagsFromApi();
    }

    /**
     * Get all the flags for the current environment for a given identity. Will also
     *  upsert all traits to the Flagsmith API for future evaluations.
     *
     * Providing a trait with a value of None will remove the trait from the identity if it exists.
     *
     * To send a transient trait, use an object as value:
     *
     * `$flagsmith->getIdentityFlags($identifier, (object)['transient' => ['value' => 'no-persist', 'transient' => true]])->allFlags();`
     *
     * @param string $identifier
     * @param object|null $traits
     * @return Flags
     *
     * @throws FlagsmithThrowable
     */
    public function getIdentityFlags(string $identifier, ?object $traits = null, ?bool $transient = false): Flags
    {
        $traits = $traits ?? (object)[];
        if (($this->offlineMode || $this->enableLocalEvaluation) && $this->environment) {
            return $this->getIdentityFlagsFromDocument($identifier, $traits);
        }

        return $this->getIdentityFlagsFromApi($identifier, $traits, $transient);
    }

    /**
     * Get a list of segments that the given identity is in.
     * @param string $identifier a unique identifier for the identity in the current
     *      environment , e.g. email address, username, uuid
     * @param object|null $traits a dictionary of traits to add / update on the identity in
     *      Flagsmith, e.g. {"num_orders": 10}
     * @return array
     *
     * @throws FlagsmithThrowable
     */
    public function getIdentitySegments(string $identifier, ?object $traits = null): array
    {
        if (empty($this->environment)) {
            throw new FlagsmithClientError('Local evaluation required to obtain identity segments.');
        }

        $traits = $traits ?? (object)[];
        $identityModel = $this->getIdentityModel($identifier, $traits);
        $segmentModels = SegmentEvaluator::getIdentitySegments($this->environment, $identityModel);

        return array_map(fn ($segment) => (new Segment())
            ->withId($segment->getId())
            ->withName($segment->getName()), $segmentModels);
    }

    /**
     * Externalised method to update the environement cache.
     * @return void
     *
     * @throws FlagsmithThrowable
     */
    public function updateEnvironment()
    {
        if (is_int($this->environmentTtl)) {
            $this->environment = $this->getEnvironmentFromApi();
        }
    }

    /**
     * Get the environment API.
     * @return EnvironmentModel
     *
     * @throws FlagsmithAPIError
     */
    private function getEnvironmentFromApi(): EnvironmentModel
    {
        $environmentDict = $this->cachedCall('Environment', 'GET', $this->environment_url, [], false, $this->environmentTtl);

        return EnvironmentModel::build($environmentDict);
    }

    /**
     * Get the environment flags from document.
     * @return Flags
     */
    private function getEnvironmentFlagsFromDocument(): Flags
    {
        return Flags::fromFeatureStateModels(
            new FeatureStateModelList(Engine::getEnvironmentFeatureStates($this->environment)),
            $this->analyticsProcessor,
            $this->defaultFlagHandler
        );
    }

    /**
     * Get identiity flags from document
     * @param string $identifier
     * @param object $traits
     * @return Flags
     *
     * @throws FlagsmithClientError
     */
    private function getIdentityFlagsFromDocument(string $identifier, object $traits): Flags
    {
        $identityModel = $this->getIdentityModel($identifier, $traits);
        $featureStates = Engine::getIdentityFeatureStates($this->environment, $identityModel);

        return Flags::fromFeatureStateModels(
            new FeatureStateModelList($featureStates),
            $this->analyticsProcessor,
            $this->defaultFlagHandler,
            $identityModel->compositeKey(),
        );
    }

    /**
     * Get environment flags from API.
     * @return Flags
     *
     * @throws FlagsmithAPIError
     */
    private function getEnvironmentFlagsFromApi(): Flags
    {
        try {
            $apiFlags = $this->cachedCall('Global', 'GET', $this->environment_flags_url);
            return Flags::fromApiFlags(
                (object) $apiFlags,
                $this->analyticsProcessor,
                $this->defaultFlagHandler,
            );
        } catch (FlagsmithAPIError $e) {
            if (isset($this->offlineHandler)) {
                return $this->getEnvironmentFlagsFromDocument();
            }
            if (isset($this->defaultFlagHandler)) {
                return (new Flags())
                    ->withDefaultFlagHandler($this->defaultFlagHandler);
            }
            throw $e;
        }
    }

    /**
     * Get the identity flags from API.
     *
     * @return Flags
     *
     * @throws FlagsmithAPIError
     */
    private function getIdentityFlagsFromApi(string $identifier, ?object $traits, ?bool $transient): Flags
    {
        try {
            $data = IdentitiesGenerator::generateIdentitiesData($identifier, $traits, $transient);
            $cacheKey = IdentitiesGenerator::generateIdentitiesCacheKey($identifier, $traits, $transient);
            $apiFlags = $this->cachedCall(
                $cacheKey,
                'POST',
                $this->identities_url,
                $data
            );

            return Flags::fromApiFlags(
                (object) $apiFlags->flags,
                $this->analyticsProcessor,
                $this->defaultFlagHandler,
            );
        } catch (FlagsmithAPIError $e) {
            if (isset($this->offlineHandler)) {
                return $this->getIdentityFlagsFromDocument($identifier, $traits);
            }
            if (isset($this->defaultFlagHandler)) {
                return (new Flags())
                    ->withDefaultFlagHandler($this->defaultFlagHandler);
            }

            throw $e;
        }
    }

    /**
     * Buid with identity model.
     * @param string $identifier
     * @param array|null $traits
     * @return IdentityModel
     *
     * @throws FlagsmithClientError
     */
    private function getIdentityModel(string $identifier, ?object $traits): IdentityModel
    {
        if (empty($this->environment)) {
            throw new FlagsmithClientError('Unable to build identity model when no local environment present.');
        }

        $traitModels = [];
        foreach ($traits as $key => $value) {
            $traitModels[] = (new TraitModel())
                ->withTraitKey($key)
                ->withTraitValue($value);
        }

        $identityModel = isset($this->environment->identity_overrides) ? $this->environment->identity_overrides[$identifier] ?? null : null;

        if (is_null($identityModel)) {
            return (new IdentityModel())
                ->withIdentifier($identifier)
                ->withEnvironmentApiKey($this->environment->getApiKey())
                ->withIdentityTraits(new IdentityTraitList($traitModels));
        }

        return $identityModel->withIdentityTraits(new IdentityTraitList($traitModels));
    }

    /**
     * Call the API and cache the response (If Caching is Enabled)
     *
     * @param string $cacheKey
     * @param string $method
     * @param string $uri
     * @param array $body
     * @param boolean $skipCache
     * @return object|array
     *
     * @throws FlagsmithAPIError
     */
    private function cachedCall(
        string $cacheKey,
        string $method,
        string $uri,
        array $body = [],
        bool $skipCache = false,
        ?int $ttl = null
    ) {
        if (!$this->hasCache()) {
            return $this->call($method, $uri, $body);
        }

        if (!$skipCache && !$this->skipCache()) {
            $cachedCall = $this->cache->get($cacheKey);
            if (\is_array($cachedCall) || \is_object($cachedCall)) {
                return $cachedCall;
            }
        }

        try {
            $response = $this->call($method, $uri, $body);
            $this->cache->set($cacheKey, $response, $ttl);

            return $response;
        } catch (FlagsmithAPIError $e) {
            if ($this->useCacheAsFailover) {
                $cachedCall = $this->cache->get($cacheKey);
                if (\is_array($cachedCall) || \is_object($cachedCall)) {
                    return $cachedCall;
                }
            }

            throw $e;
        }
    }

    /**
     * Call Request
     *
     * This sets up a FIG PSR-7 request and returns the response through a PSR-18 call
     *
     * Note: We use Guzzle's Request here to construct a PSR-7 RequestInterface
     *
     * @param string $method
     * @param string $uri
     * @param array $body
     * @return object|array
     *
     * @throws FlagsmithAPIError
     */
    private function call(string $method, string $uri, array $body = [])
    {
        $stream = $this->streamFactory->createStream(json_encode($body));

        $request = $this->requestFactory
            ->createRequest($method, rtrim($this->host, '/') . '/' . $uri)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-Environment-Key', $this->apiKey);

        if (!empty($this->customHeaders)) {
            foreach ($this->customHeaders as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
        }

        if ($method !== 'GET') {
            $request = $request->withBody($stream);
        }

        $response = null;
        $retry = clone ($this->retries);
        $statusCode = null;

        do {
            $retry->waitWithBackoff();
            // sets to true on last try
            $throwException = !$retry->hasRetriesRemaining();

            try {
                $response = $this->client->sendRequest($request);
            } catch (\Exception $e) {
                if ($throwException) {
                    throw new FlagsmithAPIError($e->getMessage(), $e->getCode(), $e);
                }
            }

            if ($response) {
                $statusCode = $response->getStatusCode();

                if ($statusCode === 200) {
                    // request was successful.
                    break;
                } elseif ($throwException && $statusCode !== 200) {
                    $message = $response->getBody()->getContents();
                    try {
                        $error = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
                        if (!empty($error['detail'])) {
                            $message = $error['detail'];
                        }
                    } catch (JsonException $e) {
                    }

                    throw new FlagsmithAPIError($message);
                }
            }

            $retry->retryAttempted();
        } while ($retry->isRetry($statusCode));

        if ($response) {
            //Return as array, easier to work with in PHP
            return json_decode(
                $response->getBody()->getContents(),
                false,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        throw new FlagsmithAPIError('No response received!');
    }
}
