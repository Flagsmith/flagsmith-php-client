<?php
namespace Flagsmith\Engine\Utils\Types\Context;

// TODO: Port this to https://wiki.php.net/rfc/dataclass
class EvaluationContext
{
    /** @var EnvironmentContext */
    public $environment;

    /** @var ?IdentityContext */
    public $identity;

    /** @var array<string, SegmentContext> */
    public $segments;

    /** @var array<string, FeatureContext> */
    public $features;

    /**
     * @param EnvironmentContext $environment
     * @param ?IdentityContext $identity
     * @param ?array<string, SegmentContext> $segments
     * @param ?array<string, FeatureContext> $features
     */
    public function __construct($environment, $identity, $segments, $features)
    {
        $this->environment = $environment;
        $this->identity = $identity;
        $this->segments = $segments ?? [];
        $this->features = $features ?? [];
    }
}
