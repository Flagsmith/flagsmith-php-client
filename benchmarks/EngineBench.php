<?php

namespace FlagsmithBench;

use Flagsmith\Engine\Engine;
use Flagsmith\Engine\Utils\Types\Context\EvaluationContext;
use Flagsmith\Flagsmith;
use Flagsmith\Offline\LocalFileHandler;
use PhpBench\Attributes as Bench;

#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(1000)]
#[Bench\Iterations(10)]
#[Bench\Warmup(1)]
#[Bench\OutputTimeUnit('microseconds', precision: 2)]
#[Bench\RetryThreshold(5.0)]
class EngineBench
{
    private const TEST_CASES_PATH = __DIR__ . '/../tests/Engine/EngineTests/EngineTestData/test_cases';

    /** Same contexts as the flagsmith-engine CodSpeed benchmark. */
    private const ENGINE_BENCHMARK_TEST_CASES = [
        'test_0cfd0d72-4de4-4ed7-9cfb-d80dc3dacead__default.json',
        'test_1bde8445-ca19-4bda-a9d5-3543a800fc0f__context_values.json',
        'test_in_condition_json_array_format__should_match.jsonc',
        'test_in_condition_numeric_comma_separated__should_match.jsonc',
        'test_in_condition_array_matching_value__should_match.jsonc',
    ];

    /** @var array<EvaluationContext> */
    private array $engineContexts;

    /** @var array<EvaluationContext> */
    private array $flagDependencyContexts;

    private Flagsmith $flagsmith;

    public function setUp(): void
    {
        $this->engineContexts = array_map(
            fn (string $fileName) => self::loadContext(self::TEST_CASES_PATH . "/{$fileName}"),
            self::ENGINE_BENCHMARK_TEST_CASES,
        );
        $this->flagDependencyContexts = array_map(
            fn (string $path) => self::loadContext($path),
            glob(self::TEST_CASES_PATH . '/test_flag_dependency__*.jsonc'),
        );
        $this->flagsmith = new Flagsmith(
            offlineMode: true,
            offlineHandler: new LocalFileHandler(__DIR__ . '/../tests/Data/environment.json'),
        );
    }

    public function benchEngine(): void
    {
        foreach ($this->engineContexts as $context) {
            Engine::getEvaluationResult($context);
        }
    }

    public function benchEngineFlagDependency(): void
    {
        foreach ($this->flagDependencyContexts as $context) {
            Engine::getEvaluationResult($context);
        }
    }

    public function benchLocalEvaluationEnvironmentFlags(): void
    {
        $this->flagsmith->getEnvironmentFlags();
    }

    public function benchLocalEvaluationIdentityFlags(): void
    {
        $this->flagsmith->getIdentityFlags('identity', (object) ['foo' => 'bar']);
    }

    private static function loadContext(string $path): EvaluationContext
    {
        return EvaluationContext::fromJsonObject(json5_decode(file_get_contents($path))->context);
    }
}
