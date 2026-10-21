<?php

/**
 * Steps for the Flagsmith SDK behavioural spec: https://github.com/Flagsmith/sdk-spec
 *
 * Each function implements the Gherkin step in the comment above it.
 */

declare(strict_types=1);

use Flagsmith\Flagsmith;
use Flagsmith\Models\Flags;

function asSpecFlags(Flags $flags): array
{
    return array_map(
        fn ($flag) => ['feature' => $flag->getFeatureName(), 'enabled' => $flag->getEnabled(), 'value' => $flag->getValue()],
        $flags->allFlags(),
    );
}

// Create the SDK client
function createClient(string $environmentKey, string $apiUrl): Flagsmith
{
    return new Flagsmith(apiKey: $environmentKey, host: $apiUrl);
}

// When the application gets the environment flags
function getEnvironmentFlags(Flagsmith $flagsmith): array
{
    return asSpecFlags($flagsmith->getEnvironmentFlags());
}

// When the application gets the flags for identity "{identifier}" with traits:
function getIdentityFlags(Flagsmith $flagsmith, string $identifier, object $traits): array
{
    return asSpecFlags($flagsmith->getIdentityFlags($identifier, $traits));
}
