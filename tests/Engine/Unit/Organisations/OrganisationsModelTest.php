<?php

use Flagsmith\Engine\Organisations\OrganisationModel;
use PHPUnit\Framework\TestCase;

class OrganisationsModelTest extends TestCase
{
    public function testUniqueSlugProperty()
    {
        $orgId = 1;
        $orgName = 'test';

        $organisation = (new OrganisationModel())
            ->withId($orgId)
            ->withName($orgName)
            ->withFeatureAnalytics(false)
            ->withStopServingFlags(false)
            ->withPersistTraitData(false);

        $this->assertEquals($organisation->uniqueSlug(), "{$orgId}-{$orgName}");
    }
}
