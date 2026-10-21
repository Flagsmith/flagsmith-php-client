<?php

namespace Flagsmith\Engine\Utils;

use Flagsmith\Engine\Engine;
use Flagsmith\Engine\Utils\Types\Context\EvaluationContext;
use Flagsmith\Engine\Utils\Types\Context\FeatureContext;
use Flagsmith\Engine\Utils\Types\Context\SegmentContext;
use Flagsmith\Engine\Utils\Types\Result\FlagResult;

/**
 * The `$.flags` mapping, resolving a flag when a condition first reads it.
 *
 * State is kept private so that it cannot be reached by a JSONPath query.
 *
 * @internal
 * @implements \ArrayAccess<string, FlagResult>
 */
final class LazyFlags implements \ArrayAccess
{
    public const CIRCULAR_DEPENDENCY_REASON = 'ERROR; code=CIRCULAR_DEPENDENCY';

    /** @var array<string, FlagResult> */
    private array $flags = [];

    /**
     * Names of the flags found to be in a dependency cycle.
     * @var array<string, true>
     */
    private array $cyclic = [];

    /**
     * Set the first time a condition reads a flag. Distinguishes "no
     * dependency was ever consulted" from "one was, and resolved to nothing",
     * which an empty mapping cannot.
     */
    private bool $used = false;

    /**
     * Incremented whenever a cycle is cut. A segment whose own evaluation
     * increments it read a flag that could not be resolved, so its verdict
     * rests on that flag's absence and it must not match.
     */
    private int $cycleHits = 0;

    /**
     * Stack of the features currently being resolved.
     * @var array<string>
     */
    private array $resolving = [];

    /** @var array<string, bool> */
    private array $segmentMatches = [];

    /** @var ?array<string, array<string>> */
    private ?array $segmentKeysByFeatureName = null;

    public function __construct(private EvaluationContext $context)
    {
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function getCycleHits(): int
    {
        return $this->cycleHits;
    }

    /** @return array<string, FlagResult> */
    public function getResolved(): array
    {
        return $this->flags;
    }

    /** @return array<string> */
    public function getCyclic(): array
    {
        return array_keys($this->cyclic);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->offsetGet($offset) !== null;
    }

    public function offsetGet(mixed $offset): ?FlagResult
    {
        $featureName = (string) $offset;
        if (!array_key_exists($featureName, $this->flags)) {
            $this->used = true;
            $this->resolveFeature($featureName);
        }
        return $this->flags[$featureName] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('$.flags is read-only');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('$.flags is read-only');
    }

    /**
     * Resolve the flag for a feature, applying any segment override.
     * @param string $featureName
     * @return void
     */
    private function resolveFeature(string $featureName): void
    {
        $cycleStart = array_search($featureName, $this->resolving, strict: true);
        if ($cycleStart !== false) {
            // Cyclic dependency. Leave the flag unresolved.
            foreach (array_slice($this->resolving, $cycleStart) as $cyclicFeatureName) {
                $this->cyclic[$cyclicFeatureName] = true;
            }
            $this->cycleHits++;
            return;
        }

        $feature = $this->context->features[$featureName] ?? null;
        if ($feature === null) {
            // Depending on a feature absent from the context.
            return;
        }

        $this->resolving[] = $featureName;
        try {
            [$overrideFeature, $overrideSegment] = $this->getSegmentOverride($featureName);
        } finally {
            array_pop($this->resolving);
        }

        if (isset($this->cyclic[$featureName])) {
            // The result is not something another condition may match on.
            return;
        }

        $this->flags[$featureName] = $overrideFeature !== null
            ? Engine::getFlagResultFromFeatureContext(
                $overrideFeature,
                $this->context->identity?->key,
                "TARGETING_MATCH; segment={$overrideSegment->name}",
            )
            : Engine::getFlagResultFromFeatureContext(
                $feature,
                $this->context->identity?->key,
            );
    }

    /**
     * @param string $segmentKey
     * @return bool
     */
    private function matchesSegment(string $segmentKey): bool
    {
        if (array_key_exists($segmentKey, $this->segmentMatches)) {
            return $this->segmentMatches[$segmentKey];
        }

        $cycleHits = $this->cycleHits;
        $matches = Engine::isContextInSegment($this->context, $this->context->segments[$segmentKey]);

        if ($this->cycleHits !== $cycleHits) {
            // Reached by cutting a cycle, so the verdict rests on a flag that
            // could not be resolved. Not a match, and not worth memoising.
            return false;
        }

        $this->segmentMatches[$segmentKey] = $matches;
        return $matches;
    }

    /**
     * @param string $featureName
     * @return array{?FeatureContext, ?SegmentContext} The winning override and its segment
     */
    private function getSegmentOverride(string $featureName): array
    {
        if ($this->segmentKeysByFeatureName === null) {
            $this->segmentKeysByFeatureName = [];
            foreach ($this->context->segments as $segmentKey => $segment) {
                foreach ($segment->overrides as $override) {
                    $this->segmentKeysByFeatureName[$override->name][] = $segmentKey;
                }
            }
        }

        $overrideFeature = null;
        $overrideSegment = null;
        foreach ($this->segmentKeysByFeatureName[$featureName] ?? [] as $segmentKey) {
            if (!$this->matchesSegment($segmentKey)) {
                continue;
            }
            $segment = $this->context->segments[$segmentKey];
            foreach ($segment->overrides as $override) {
                if ($override->name !== $featureName) {
                    continue;
                }
                if (Engine::overrideWins($override, $overrideFeature)) {
                    $overrideFeature = $override;
                    $overrideSegment = $segment;
                }
            }
        }

        return [$overrideFeature, $overrideSegment];
    }
}
