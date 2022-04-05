<?php

namespace Flagsmith\Engine\Utils\Collections;

use Flagsmith\Engine\Utils\Exceptions\FeatureStateNotFound;

class FeatureStateModelList extends \ArrayObject implements \JsonSerializable
{
    use CollectionTrait;
    private string $list_type = 'Flagsmith\Engine\Features\FeatureStateModel';

    /**
     * Get the feature state model with feature name.
     * @param string $featureName
     * @return mixed
     */
    public function getFeatureStateModel(string $featureName)
    {
        foreach ($this as $featureStateModel) {
            if ($featureStateModel->getFeature()->getName() === $featureName) {
                return $featureStateModel;
            }
        }

        throw new FeatureStateNotFound();
    }
}
