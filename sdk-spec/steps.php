<?php

/**
 * Steps for the Flagsmith SDK behavioural spec: https://github.com/Flagsmith/sdk-spec
 *
 * Each function implements the Gherkin step in the comment above it.
 */

declare(strict_types=1);

use Flagsmith\Flagsmith;
use Flagsmith\Models\Flag;
use Flagsmith\Models\Flags;

function asSpecFlag(Flag $flag): array
{
    return ['feature' => $flag->getFeatureName(), 'enabled' => $flag->getEnabled(), 'value' => $flag->getValue()];
}

function asSpecFlags(Flags $flags): array
{
    return array_map(asSpecFlag(...), $flags->allFlags());
}

// Create the SDK client
function createClient(string $environmentKey, string $apiUrl): Flagsmith
{
    return new Flagsmith(apiKey: $environmentKey, host: $apiUrl);
}

// Create the SDK client with local evaluation
function createClientWithLocalEvaluation(string $environmentKey, string $apiUrl): Flagsmith
{
    $flagsmith = new Flagsmith(apiKey: $environmentKey, host: $apiUrl, environmentTtl: 60);
    $flagsmith->updateEnvironment();
    return $flagsmith;
}

// Create the SDK client with events enabled
function createClientWithEvents(string $environmentKey, string $apiUrl, string $eventsApiUrl): Flagsmith
{
    return new Flagsmith(apiKey: $environmentKey, host: $apiUrl, enableEvents: true, eventsApiUrl: $eventsApiUrl);
}

// Create the SDK client with local evaluation and events enabled
function createClientWithLocalEvaluationAndEvents(string $environmentKey, string $apiUrl, string $eventsApiUrl): Flagsmith
{
    $flagsmith = new Flagsmith(apiKey: $environmentKey, host: $apiUrl, environmentTtl: 60, enableEvents: true, eventsApiUrl: $eventsApiUrl);
    $flagsmith->updateEnvironment();
    return $flagsmith;
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

// When the application gets the experiment flag "{feature}" for identity "{identifier}" with traits:
function getExperimentFlag(Flagsmith $flagsmith, string $feature, string $identifier, object $traits): array
{
    $flag = $flagsmith->getExperimentFlag($feature, $identifier, $traits);
    $flagsmith->flushEvents(); // Events are sent when a PHP request ends, which the harness's long-lived process never does.

    return $flag instanceof Flag ? [asSpecFlag($flag)] : [];
}

// When the application tracks event "{event}" for identity "{identifier}" with value {value} and metadata:
function trackEvent(Flagsmith $flagsmith, string $event, string $identifier, string|int|float|bool|null $value, array $metadata): void
{
    $flagsmith->trackEvent($event, identifier: $identifier, value: $value, metadata: $metadata);
    $flagsmith->flushEvents(); // Events are sent when a PHP request ends, which the harness's long-lived process never does.
}

// When the application tracks an exposure of feature "{feature}" for identity "{identifier}" with value {value} and metadata:
function trackExposureEvent(Flagsmith $flagsmith, string $feature, string $identifier, string|int|float|bool|null $value, array $metadata): void
{
    $flagsmith->trackExposureEvent($feature, identifier: $identifier, value: $value, metadata: $metadata);
    $flagsmith->flushEvents(); // Events are sent when a PHP request ends, which the harness's long-lived process never does.
}

// When the application tracks events "event_{first}" to "event_{last}" for identity "{identifier}"
function trackEvents(Flagsmith $flagsmith, int $first, int $last, string $identifier): void
{
    // Tracked in one go, then sent when the application flushes events.
    for ($number = $first; $number <= $last; $number++) {
        $flagsmith->trackEvent("event_{$number}", identifier: $identifier);
    }
}

// When the application flushes events
function flushEvents(Flagsmith $flagsmith): void
{
    $flagsmith->flushEvents();
}

// When the application closes the client with a timeout of {timeout} seconds
function closeClient(Flagsmith $flagsmith, int|float $timeout): void
{
    $flagsmith->close($timeout);
}
