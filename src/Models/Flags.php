<?php

namespace Flagsmith\Models;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Engine\Utils\Types\Result\EvaluationResult;
use Flagsmith\Utils\Collections\FlagModelsList;
use Flagsmith\Engine\Utils\Collections\FeatureStateModelList;
use Flagsmith\Exceptions\FlagsmithClientError;
use Flagsmith\Utils\AnalyticsProcessor;

class Flags
{
    use HasWith;
    public FlagModelsList $flags;

    public ?\Closure $default_flag_handler;

    public ?AnalyticsProcessor $analytics_processor;

    public function __construct()
    {
        $this->flags = new FlagModelsList([]);
    }

    /**
     * Get the Default Flag Handler
     * @return \Closure
     */
    public function getDefaultFlagHandler(): ?\Closure
    {
        return $this->default_flag_handler;
    }

    /**
     * Build with default flag handler
     * @param \Closure $default_flag_handler
     * @return Flags
     */
    public function withDefaultFlagHandler(?\Closure $default_flag_handler): self
    {
        return $this->with('default_flag_handler', $default_flag_handler);
    }

    /**
     * Get the flags list.
     * @return FlagModelsList
     */
    public function getFlags(): FlagModelsList
    {
        return $this->flags;
    }

    /**
     * Build with flags list.
     * @param FlagModelsList $flags
     * @return Flags
     */
    public function withFlags(FlagModelsList $flags): self
    {
        return $this->with('flags', $flags);
    }

    /**
     * Get the analytics processor.
     * @return AnalyticsProcessor
     */
    public function getAnalyticsProcessor(): ?AnalyticsProcessor
    {
        return $this->analytics_processor;
    }

    /**
     * Build with Analytics Processor.
     * @param AnalyticsProcessor $analyticsProcessor
     * @return Flags
     */
    public function withAnalyticsProcessor(?AnalyticsProcessor $analyticsProcessor): self
    {
        return $this->with('analytics_processor', $analyticsProcessor);
    }

    /**
     * Build Flags from Evaluation Result.
     * @param EvaluationResult $evaluationResult
     * @param ?AnalyticsProcessor $analyticsProcessor
     * @param ?\Closure $defaultFlagHandler
     * @return void
     */
    public static function fromEvaluationResult(
        $evaluationResult,
        ?AnalyticsProcessor $analyticsProcessor,
        ?\Closure $defaultFlagHandler,
    ): Flags {
        $flags = [];
        foreach ($evaluationResult->flags as $flagResult) {
            $flag = new Flag();
            $flag->feature_name = $flagResult->name;
            $flag->feature_id = (int) $flagResult->feature_key;
            $flag->enabled = $flagResult->enabled;
            $flag->value = $flagResult->value;
            $flags[$flagResult->name] = $flag;
        }

        $_this = new self();
        $_this->flags = new FlagModelsList($flags);
        $_this->default_flag_handler = $defaultFlagHandler;
        $_this->analytics_processor = $analyticsProcessor;
        return $_this;
    }

    /**
     * Build with Feature State Models.
     * @param FeatureStateModelList $featureStateModelsList
     * @param AnalyticsProcessor $analyticsProcessor
     * @param \Closure $defaultFlagHandler
     * @param mixed $identityId
     * @return Flags
     */
    public static function fromFeatureStateModels(
        FeatureStateModelList $featureStateModelsList,
        ?AnalyticsProcessor $analyticsProcessor,
        ?\Closure $defaultFlagHandler,
        $identityId = null
    ) {
        $flags = [];
        foreach ($featureStateModelsList->getArrayCopy() as $featureState) {
            $flags[$featureState->getFeature()->getName()] = Flag::fromFeatureStateModel($featureState, $identityId);
        }

        return (new self())
            ->withFlags(new FlagModelsList($flags))
            ->withDefaultFlagHandler($defaultFlagHandler)
            ->withAnalyticsProcessor($analyticsProcessor);
    }

    /**
     * Build with Flags API response.
     * @param array $apiFlags
     * @param AnalyticsProcessor $analyticsProcessor
     * @param \Closure $defaultFlagHandler
     * @param mixed $identityId
     * @return Flags
     */
    public static function fromApiFlags(
        object $apiFlags,
        ?AnalyticsProcessor $analyticsProcessor,
        ?\Closure $defaultFlagHandler,
        $identityId = null
    ) {
        $flags = [];
        foreach ($apiFlags as $apiFlag) {
            $flags[$apiFlag->feature->name] = Flag::fromApiFlag($apiFlag, $identityId);
        }

        return (new self())
            ->withFlags(new FlagModelsList($flags))
            ->withDefaultFlagHandler($defaultFlagHandler)
            ->withAnalyticsProcessor($analyticsProcessor);
    }

    /**
     * Return with list of Flag objects.
     * @return array
     */
    public function allFlags(): array
    {
        return array_values($this->flags->getArrayCopy());
    }

    /**
     * Is the feature enabled.
     * @param string $featureName
     * @return bool
     */
    public function isFeatureEnabled(string $featureName): bool
    {
        return $this->getFlag($featureName)->getEnabled();
    }

    /**
     * Get the value of the feature name.
     * @param string $featureName
     * @return mixed
     */
    public function getFeatureValue(string $featureName)
    {
        return $this->getFlag($featureName)->getValue();
    }

    /**
     * Get flag object by feature name.
     * @param string $featureName
     * @return BaseFlag
     */
    public function getFlag(string $featureName): BaseFlag
    {
        $flag = null;
        if (isset($this->flags->{$featureName})) {
            $flag = $this->flags->{$featureName};
        } else {
            if (isset($this->default_flag_handler)) {
                return $this->default_flag_handler->call($this, $featureName);
            }

            throw new FlagsmithClientError('Feature does not exist');
        }

        if (isset($this->analytics_processor) && !empty($flag) && isset($flag->feature_name)) {
            $this->analytics_processor->trackFeature($flag->feature_name);
        }

        return $flag;
    }
}
