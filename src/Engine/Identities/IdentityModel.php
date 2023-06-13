<?php

namespace Flagsmith\Engine\Identities;

use Flagsmith\Concerns\HasWith;
use DateTime;
use Flagsmith\Concerns\JsonSerializer;
use Flagsmith\Engine\Utils\Collections\IdentityFeaturesList;
use Flagsmith\Engine\Utils\Collections\IdentityTraitList;
use Flagsmith\Engine\Utils\UniqueUID;

class IdentityModel
{
    use HasWith;
    use JsonSerializer;
    public string $identifier;
    public string $environment_api_key;
    public DateTime $created_date;
    public IdentityFeaturesList $identity_features;
    public IdentityTraitList $identity_traits;
    public string $identity_uuid;
    public ?int $django_id = null;

    protected array $keys = [
        'identity_features' => 'Flagsmith\Engine\Utils\Collections\IdentityFeaturesList',
        'identity_traits' => 'Flagsmith\Engine\Utils\Collections\IdentityTraitList',
        'created_date' => '\DateTime',
    ];

    public function __construct()
    {
        $this->identity_uuid = UniqueUID::v4();
        $this->identity_features = new IdentityFeaturesList();
        $this->identity_traits = new IdentityTraitList();
    }

    /**
     * Get the environment API key.
     * @return string
     */
    public function getEnvironmentApiKey(): string
    {
        return $this->environment_api_key;
    }

    /**
     * Build with Environment API Key.
     * @param string $environment_api_key
     * @return IdentityModel
     */
    public function withEnvironmentApiKey(string $environment_api_key): self
    {
        return $this->with('environment_api_key', $environment_api_key);
    }

    /**
     * Get the identifier.
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Build with identifier.
     * @param string $identifier
     * @return IdentityModel
     */
    public function withIdentifier(string $identifier): self
    {
        return $this->with('identifier', $identifier);
    }

    /**
     * Get the django ID.
     * @return int
     */
    public function getDjangoId(): ?int
    {
        return $this->django_id;
    }

    /**
     * Build with Django ID.
     * @param int $django_id
     * @return IdentityModel
     */
    public function withDjangoId(int $django_id): self
    {
        return $this->with('django_id', $django_id);
    }

    /**
     * Get the identity UUID.
     * @return string
     */
    public function getIdentityUuid(): string
    {
        return $this->identity_uuid;
    }

    /**
     * Build with identity UUID.
     * @param string $identityUuid
     * @return IdentityModel
     */
    public function withIdentityUuid(string $identityUuid): self
    {
        return $this->with('identity_uuid', $identityUuid);
    }

    /**
     * Get the identity Traits.
     * @return IdentityTraitList
     */
    public function getIdentityTraits(): IdentityTraitList
    {
        return $this->identity_traits;
    }

    /**
     * Build with identity Traits.
     * @param IdentityTraitList $identityTraits
     * @return IdentityModel
     */
    public function withIdentityTraits(IdentityTraitList $identityTraits): self
    {
        return $this->with('identity_traits', $identityTraits);
    }

    /**
     * Get the identity Features.
     * @return IdentityFeaturesList
     */
    public function getIdentityFeatures(): IdentityFeaturesList
    {
        return $this->identity_features;
    }

    /**
     * Build with identity Features.
     * @param IdentityFeaturesList $identityFeatures
     * @return IdentityModel
     */
    public function withidentityFeatures(IdentityFeaturesList $identityFeatures): self
    {
        return $this->with('identity_features', $identityFeatures);
    }

    /**
     * Get the created date.
     * @return DateTime
     */
    public function getCreatedDate(): DateTime
    {
        return $this->created_date;
    }

    /**
     * Build with created date.
     * @param DateTime $createdDate
     * @return IdentityModel
     */
    public function withCreatedDate(DateTime $createdDate): self
    {
        return $this->with('created_date', $createdDate);
    }

    /**
     * Get the composite key.
     * @return string
     */
    public function compositeKey(): string
    {
        return self::generateCompositeKey($this->environment_api_key, $this->identifier);
    }

    /**
     * Generate the composite key.
     * @param string $envKey
     * @param string $identifier
     * @return string
     */
    public static function generateCompositeKey(string $envKey, string $identifier): string
    {
        return "{$envKey}_{$identifier}";
    }

    /**
     * Replace/remove the identity traits.
     * @param array $traits
     * @return void
     */
    public function updateTraits(array $traits = [])
    {
        $existingTraits = [];
        foreach ($this->identity_traits as $trait) {
            $existingTraits[$trait->getTraitKey()] = $trait;
        }

        foreach ($traits as $trait) {
            if (empty($trait->getTraitValue())) {
                unset($existingTraits[$trait->getTraitKey()]);
            } else {
                $existingTraits[$trait->getTraitKey()] = $trait;
            }
        }

        $this->identity_traits = new IdentityTraitList(array_values($existingTraits));
    }
}
