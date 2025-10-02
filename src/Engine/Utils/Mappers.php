<?php

namespace Flagsmith\Engine\Utils\Mappers;

use Flagsmith\Engine\Utils\Types\Context\EvaluationContext;
use Flagsmith\Engine\Utils\Types\Context\IdentityContext;

class Mappers
{
    /**
     * @param EvaluationContext $context
     * @param string $identifier
     * @param object $traits
     * @return EvaluationContext
     */
    public static function mapContextAndIdentityToContext($context, $identifier, $traits): EvaluationContext
    {
        $identity = new IdentityContext();
        $identity->identifier = $identifier;
        $identity->key = "{$context->environment->key}_{$identifier}";
        $identity->traits = (array) $traits;

        $context = $context->deepClone();
        $context->identity = $identity;
        return $context;
    }
}
