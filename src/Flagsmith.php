<?php

namespace Flagsmith;

use DateTimeImmutable;
use Flagsmith\Concerns\HasWith;
use Flagsmith\Exceptions\APIException;
use Flagsmith\Models\Feature;
use Flagsmith\Models\Flag;
use Flagsmith\Models\Identity;
use Flagsmith\Models\IdentityTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\SimpleCache\CacheInterface;

class Flagsmith
{
    use HasWith;

    private string $apiKey;
    private string $host;
    private ClientInterface $client;
    private ?Cache $cache = null;
    private ?int $timeToLive = null;
    private string $cachePrefix = 'flagsmith';

    public function __construct(
        string $apiKey,
        string $host = 'https://api.flagsmith.com/api/v1/'
    ) {
        $this->apiKey = $apiKey;
        $this->host = $host;
        $this->client = new Client();
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
     * Get all Global Flags
     *
     * @return array
     */
    public function getFlags(): array
    {
        return $this->mapFlags($this->cachedCall('global', 'GET', 'flags/'));
    }

    /**
     * Get Identity by Identity
     *
     * @param string $identifier
     * @return Identity
     */
    public function getIdentityByIndentity(Identity $identity): Identity
    {
        $id = $identity->getId();
        if ($identity->hasTraits()) {
            $response = $this->cachedIdentityCall(
                $identity,
                'POST',
                'identities/',
                [
                    'identifier' => $identity->getId(),
                    'traits' => array_map(
                        fn(IdentityTrait $trait) => [
                            'trait_key' => $trait->getKey(),
                            'trait_value' => $trait->getValue(),
                        ],
                        $identity->getTraits()
                    ),
                ]
            );
        } else {
            $response = $this->cachedCall(
                "identity.{$id}",
                'GET',
                "identities/?identifier={$identity->getId()}"
            );
        }

        return $identity
            ->withFlags($this->mapFlags($response['flags']))
            ->withTraits($this->mapTraits($response['traits']));
    }

    /**
     * Get Flags by Identity
     *
     * @param Identity $identity
     * @return array
     */
    public function getFlagsByIdentity(Identity $identity): array
    {
        $identity = $this->getIdentityByIndentity($identity);
        return $identity->getFlags();
    }

    /**
     * Get Traits by Identity
     *
     * @param Identity $identity
     * @return array
     */
    public function getTraitsByIdentity(Identity $identity): array
    {
        $identity = $this->getIdentityByIndentity($identity);
        return $identity->getTraits();
    }

    /**
     * Get a Single Flag
     *
     * @param string $name
     * @return Flag|null
     */
    public function getFlag(string $name): ?Flag
    {
        $key = $this->normalizeKey($name);
        return $this->getFlags()[$key] ?? null;
    }

    /**
     * Get a Single Flag by Identity
     *
     * @param Identity $identity
     * @param string $name
     * @return Flag|null
     */
    public function getFlagByIdentity(Identity $identity, string $name): ?Flag
    {
        $key = $this->normalizeKey($name);
        return $this->getFlagsByIdentity($identity)[$key] ?? null;
    }

    /**
     * Check if Feature is Enabled
     *
     * @param string $name
     * @param boolean $default
     * @return boolean
     */
    public function isFeatureEnabled(string $name, bool $default = false): bool
    {
        $flag = $this->getFlag($name);
        return !is_null($flag) ? $flag->getEnabled() : $default;
    }

    /**
     * Is feature enabled by identity
     *
     * @param Identity $identity
     * @param string $name
     * @param boolean $default
     * @return boolean
     */
    public function isFeatureEnabledByIdentity(
        Identity $identity,
        string $name,
        bool $default = false
    ): bool {
        $flag = $this->getFlagByIdentity($identity, $name);
        return !is_null($flag) ? $flag->getEnabled() : $default;
    }

    /**
     * Get Value of Flag
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getFeatureValue(string $name, $default = null)
    {
        $flag = $this->getFlag($name);
        return !is_null($flag) ? $flag->getFeatureStateValue() : $default;
    }

    /**
     * Get Value of Flag By Identity
     *
     * @param Identity $identity
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getFeatureValueByIdentity(
        Identity $identity,
        string $name,
        $default = null
    ) {
        $flag = $this->getFlagByIdentity($identity, $name);
        return !is_null($flag) ? $flag->getFeatureStateValue() : $default;
    }

    /**
     * Set Traits by Identity
     *
     * @param Identity $identity
     * @return boolean
     */
    public function setTraitsByIdentity(Identity $identity): bool
    {
        if (!$identity->hasTraits()) {
            throw new InvalidArgumentException('There are no traits to set');
        }
        $this->call(
            'PUT',
            'traits/bulk/',
            array_map(
                fn(IdentityTrait $trait) => [
                    'identity' => ['identifier' => $identity->getId()],
                    'trait_key' => $trait->getKey(),
                    'trait_value' => $trait->getValue(),
                ],
                $identity->getTraits()
            )
        );

        return true;
    }

    /**
     * Call the API with an Identity and cache the response
     *
     * @param Identity $identity
     * @param string $method
     * @param string $uri
     * @param array $body
     * @return array
     */
    private function cachedIdentityCall(
        Identity $identity,
        string $method,
        string $uri,
        array $body = []
    ): array {
        if (!$this->hasCache()) {
            return $this->call($method, $uri, $body);
        }

        if (
            !$identity->hasTraits() ||
            !$this->cache->has("identity.{$identity->getId()}")
        ) {
            return $this->cachedCall(
                "identity.{$identity->getId()}",
                $method,
                $uri,
                $body
            );
        }

        //We have traits. Compare the cached traits
        //If they differ then we need to send this request
        //Regardless of the cache

        $res = $this->cache->get("identity.{$identity->getId()}");

        $originalTraits = array_reduce(
            $res['traits'],
            function (array $carry, array $item) {
                $carry[$item['trait_key']] = $item['trait_value'];
                return $carry;
            },
            []
        );

        $newTraits = array_reduce(
            $identity->getTraits(),
            function (array $carry, IdentityTrait $item) {
                $carry[$item->getKey()] = $item->getValue();
                return $carry;
            },
            []
        );

        $force = false;
        foreach ($newTraits as $key => $value) {
            if (
                !isset($originalTraits[$key]) ||
                $originalTraits[$key] !== $value
            ) {
                $force = true;
                break;
            }
        }

        return $this->cachedCall(
            "identity.{$identity->getId()}",
            $method,
            $uri,
            $body,
            $force
        );
    }

    /**
     * Call the API and cache the response
     *
     * @param string $cacheKey
     * @param string $method
     * @param string $uri
     * @param array $body
     * @param boolean $force
     * @return array
     */
    private function cachedCall(
        string $cacheKey,
        string $method,
        string $uri,
        array $body = [],
        bool $force = false
    ): array {
        if (!$this->hasCache()) {
            return $this->call($method, $uri, $body);
        }

        if ($force || !$this->cache->has($cacheKey)) {
            $response = $this->call($method, $uri, $body);
            $this->cache->set($cacheKey, $response);
        }

        return $this->cache->get($cacheKey);
    }

    /**
     * Call Request
     *
     * This sets up a FIG PSR-7 request and returns the response through a PSR-18 call
     *
     * @param string $method
     * @param string $uri
     * @param array $body
     * @return array
     */
    private function call(string $method, string $uri, array $body = []): array
    {
        $request = new Request(
            $method,
            rtrim($this->host, '/') . '/' . $uri,
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Environment-Key' => $this->apiKey,
            ],
            json_encode($body)
        );

        try {
            $response = $this->client->sendRequest($request);
        } catch (RequestExceptionInterface $e) {
            throw new APIException($e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() !== 200) {
            $message = $response->getBody()->getContents();
            try {
                $error = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
                if (!empty($error['detail'])) {
                    $message = $error['detail'];
                }
            } catch (JsonException $e) {
            }

            throw new APIException($message);
        }

        //Return as array, easier to work with in PHP
        return json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * Normalize Key
     *
     * @param string $key
     * @return string
     */
    private function normalizeKey(string $key): string
    {
        return strtolower($key);
    }

    /**
     * Map Flags to their respective classes
     *
     * @param array $flags
     * @return array
     */
    private function mapFlags(array $flags): array
    {
        return array_reduce(
            $flags,
            function (array $carry, array $flag) {
                $feature = (new Feature())
                    ->withId($flag['feature']['id'])
                    ->withName($flag['feature']['name'])
                    ->withCreatedDate(
                        new DateTimeImmutable($flag['feature']['created_date'])
                    )
                    ->withDescription($flag['feature']['description'])
                    ->withInitialValue($flag['feature']['initial_value'])
                    ->withDefaultEnabled($flag['feature']['default_enabled'])
                    ->withType($flag['feature']['type']);

                $carry[
                    $this->normalizeKey($flag['feature']['name'])
                ] = (new Flag())
                    ->withId($flag['id'])
                    ->withFeature($feature)
                    ->withFeatureStateValue($flag['feature_state_value'])
                    ->withEnabled($flag['enabled'])
                    ->withEnvironment($flag['environment'])
                    ->withFeatureSegment($flag['feature_segment']);

                return $carry;
            },
            []
        );
    }

    /**
     * Map Traits to their respective classes
     *
     * @param array $traits
     * @return array
     */
    private function mapTraits(array $traits): array
    {
        return array_reduce(
            $traits,
            function (array $carry, array $trait) {
                $carry[
                    $this->normalizeKey($trait['trait_key'])
                ] = (new IdentityTrait($trait['trait_key']))
                    ->withId($trait['id'])
                    ->withValue($trait['trait_value']);
                return $carry;
            },
            []
        );
    }
}
