<?php

namespace Flagsmith\Engine\Environments;

use DateTime;
use Flagsmith\Concerns\HasWith;
use Flagsmith\Concerns\JsonSerializer;

class EnvironmentAPIKeyModel
{
    use HasWith;
    use JsonSerializer;
    public int $id;
    public string $key;
    public DateTime $created_at;
    public string $name;
    public string $client_api_key;
    public ?DateTime $expires_at = null;
    public bool $active = true;
    private array $keys = [
        'created_at' => '\DateTime',
        'expires_at' => '\DateTime',
    ];


    /**
     * Return the Id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Build with Id.
     *
     * @param int $id
     * @return EnvironmentAPIKeyModel
     */
    public function withId(int $id): self
    {
        return $this->with('id', $id);
    }

    /**
     * Return the Key.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Build with key.
     * @param string $key
     * @return EnvironmentAPIKeyModel
     */
    public function withKey(string $key): self
    {
        return $this->with('key', $key);
    }

    /**
     * Return the created at date.
     *
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->created_at;
    }

    /**
     * Build with created date.
     * @param DateTime $createdAt
     * @return EnvironmentAPIKeyModel
     */
    public function withCreatedAt(DateTime $createdAt): self
    {
        return $this->with('created_at', $createdAt);
    }

    /**
     * Return the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Build with Name.
     * @param string $name
     * @return EnvironmentAPIKeyModel
     */
    public function withName(string $name): self
    {
        return $this->with('name', $name);
    }

    /**
     * Return the client key api.
     *
     * @return string
     */
    public function getClientApiKey(): string
    {
        return $this->client_api_key;
    }

    /**
     * build with client API Key.
     * @param string $clientApiKey
     * @return EnvironmentAPIKeyModel
     */
    public function withClientApiKey(string $clientApiKey): self
    {
        return $this->with('client_api_key', $clientApiKey);
    }

    /**
     * Return the expires at date.
     *
     * @return DateTime
     */
    public function getExpiresAt(): DateTime
    {
        return $this->expires_at;
    }

    /**
     * Build with ExpiresAt.
     * @param DateTime $expiresAt
     * @return EnvironmentAPIKeyModel
     */
    public function withExpiresAt(DateTime $expiresAt): self
    {
        return $this->with('expires_at', $expiresAt);
    }

    /**
     * Return the active state.
     *
     * @return bool
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * build with active state.
     * @param bool $active
     * @return EnvironmentAPIKeyModel
     */
    public function withActive(bool $active): self
    {
        return $this->with('active', $active);
    }

    public function isValid(): bool
    {
        return $this->active && (
            !$this->expires_at || ($this->expires_at > new DateTime('now'))
        );
    }
}
