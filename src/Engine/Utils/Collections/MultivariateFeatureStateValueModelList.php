<?php

namespace Flagsmith\Engine\Utils\Collections;

use Flagsmith\Engine\Features\MultivariateFeatureStateValueModel;
use Flagsmith\Engine\Utils\Exceptions\InvalidPercentageAllocation;

class MultivariateFeatureStateValueModelList extends \ArrayObject implements \JsonSerializable
{
    use CollectionTrait;
    private string $list_type = 'Flagsmith\Engine\Features\MultivariateFeatureStateValueModel';

    /**
     * Returns the object to JSON serialize.
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $stateValues = $this->getArrayCopy();
        if (count($stateValues) > 0) {
            $allocation = array_sum(array_map(fn (MultivariateFeatureStateValueModel $model) => $model->getPercentageAllocation(), $stateValues));

            if ($allocation > 100) {
                throw new InvalidPercentageAllocation('The total percentage exceeds 100.');
            }
        }

        return $this->getArrayCopy();
    }
}
