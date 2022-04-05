<?php

namespace Flagsmith\Engine\Utils\Collections;

use Flagsmith\Engine\Utils\Exceptions\DuplicateFeatureState;

class IdentityFeaturesList extends \ArrayObject implements \JsonSerializable
{
    use CollectionTrait;
    private string $list_type = 'Flagsmith\Engine\Features\FeatureStateModel';
    /**
     * check for clones before adding them to the list.
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        if (is_null($key)) {
            $list = clone $this;
            foreach ($list as $row) {
                if ($row->getFeature()->getId() === $value->getFeature()->getId()) {
                    throw new DuplicateFeatureState('feature state for this feature already exists');
                }
            }
        }

        parent::offsetSet($key, $value);
    }
}
