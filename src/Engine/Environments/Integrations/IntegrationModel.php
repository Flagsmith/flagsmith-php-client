<?php

namespace Flagsmith\Engine\Environments\Integrations;

use Flagsmith\Concerns\HasWith;

class IntegrationModel
{
    use HasWith;

    private string $api_key;
    private string $base_url;

    /**
     * Return the API Key.
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->api_key;
    }

    /**
     * Return the Base URL.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->base_url;
    }

    /**
     * Build with API Key
     * @param string $apiKey
     * @return IntegrationModel
     */
    public function withApiKey(string $apiKey): self
    {
        return $this->with('api_key', $apiKey);
    }

    /**
     * Build with Base URL
     *
     * @param string $apiKey
     * @return IntegrationModel
     */
    public function withBaseUrl(string $baseUrl): self
    {
        return $this->with('base_url', $baseUrl);
    }
}
