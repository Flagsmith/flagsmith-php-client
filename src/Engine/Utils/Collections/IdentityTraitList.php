<?php

namespace Flagsmith\Engine\Utils\Collections;

use Flagsmith\Engine\Utils\Exceptions\FeatureStateNotFound;

class IdentityTraitList extends \ArrayObject implements \JsonSerializable
{
    use CollectionTrait;
    private string $list_type = 'Flagsmith\Engine\Identities\Traits\TraitModel';
}
