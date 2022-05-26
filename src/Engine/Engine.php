<?php

namespace Flagsmith\Engine;

use Flagsmith\Engine\Environments\EnvironmentModel;
use Flagsmith\Engine\Features\FeatureStateModel;
use Flagsmith\Engine\Identities\IdentityModel;
use Flagsmith\Engine\Segments\SegmentEvaluator;
use Flagsmith\Engine\Utils\Exceptions\FeatureStateNotFound;

class Engine
{
    /**
     * Get the environment feature states.
     * @param EnvironmentModel $environment
     * @return array
     */
    public static function getEnvironmentFeatureStates(EnvironmentModel $environment): array
    {
        if ($environment->getProject()->getHideDisabledFlags()) {
            return array_filter(
                $environment->getFeatureStates()->getArrayCopy(),
                fn (FeatureStateModel $fs) => $fs->getEnabled()
            );
        }

        return $environment->getFeatureStates()->getArrayCopy();
    }

    /**
     * get the environment feature state.
     * @param EnvironmentModel $environment
     * @param string $featureName
     * @return FeatureStateModel
     */
    public static function getEnvironmentFeatureState(
        EnvironmentModel $environment,
        string $featureName
    ): FeatureStateModel {
        $featureStates = $environment
            ->getFeatureStates()
            ->getArrayCopy();
        $filteredFeatureStates = array_filter(
            $featureStates,
            fn (FeatureStateModel $fs)
                => $fs->getFeature()->getName() === $featureName
        );

        if (count($filteredFeatureStates) === 0) {
            throw new FeatureStateNotFound();
        }

        return array_shift($filteredFeatureStates);
    }

    /**
     * get the identity feature states.
     * @param EnvironmentModel $environment
     * @param IdentityModel $identity
     * @param array|null $overrideTraits
     * @return array
     */
    public static function getIdentityFeatureStates(
        EnvironmentModel $environment,
        IdentityModel $identity,
        array $overrideTraits = null
    ): array {
        $featureStates = self::_getIdentityFeatureStatesDict(
            $environment,
            $identity,
            $overrideTraits
        );

        if ($environment->getProject()->getHideDisabledFlags()) {
            $filteredFeatureStates = [];
            foreach ($featureStates as $featureState) {
                if ($featureState->getEnabled()) {
                    $filteredFeatureStates[] = $featureState;
                }
            }
            return $filteredFeatureStates;
        }

        return array_values($featureStates);
    }

    /**
     * get the identity feature state.
     * @param EnvironmentModel $environment
     * @param IdentityModel $identity
     * @param string $featureName
     * @param array|null $overrideTraits
     * @return FeatureStateModel
     */
    public static function getIdentityFeatureState(
        EnvironmentModel $environment,
        IdentityModel $identity,
        string $featureName,
        array $overrideTraits = null
    ): FeatureStateModel {
        $featureStates = self::_getIdentityFeatureStatesDict(
            $environment,
            $identity,
            $overrideTraits
        );

        $matchingFeatureState = null;
        foreach ($featureStates as $name => $featureState) {
            if ($name === $featureName) {
                $matchingFeatureState = $featureState;
            }
        }

        if (empty($matchingFeatureState)) {
            throw new FeatureStateNotFound();
        }

        return $matchingFeatureState;
    }

    /**
     * get feature states list.
     * @param EnvironmentModel $environment
     * @param IdentityModel $identity
     * @param array|null $overrideTraits
     * @return array
     */
    private static function _getIdentityFeatureStatesDict(
        EnvironmentModel $environment,
        IdentityModel $identity,
        array $overrideTraits = null
    ): array {
        $featureStates = [];
        foreach ($environment->getFeatureStates() as $fs) {
            $featureStates[$fs->getFeature()->getName()] = $fs;
        }

        $identitySegments = SegmentEvaluator::getIdentitySegments($environment, $identity, $overrideTraits);

        foreach ($identitySegments as $is) {
            foreach ($is->getFeatureStates() as $fs) {
                $feature = $fs->getFeature();
                $existing = $featureStates[$feature->getName()];
                if ($existing != null && $existing->isHigherPriority($fs))
                {
                    continue;
                }

                $featureStates[$fs->getFeature()->getName()] = $fs;
            }
        }

        foreach ($identity->getIdentityFeatures() as $if) {
            if (isset($featureStates[$if->getFeature()->getName()])) {
                $featureStates[$if->getFeature()->getName()] = $if;
            }
        }

        return $featureStates;
    }
}
