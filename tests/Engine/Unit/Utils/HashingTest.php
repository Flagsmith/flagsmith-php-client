<?php

use Flagsmith\Engine\Utils\Hashing;
use Flagsmith\Engine\Utils\UniqueUID;
use PHPUnit\Framework\TestCase;

class HashingTest extends TestCase
{
    public function objectIds()
    {
        return [
            [[12, 93]],
            [[UniqueUID::v4(), 99]],
            [[99, UniqueUID::v4()]],
            [[UniqueUID::v4(), UniqueUID::v4()]],
        ];
    }

    /**
     * @dataProvider objectIds
     */
    public function testGetHashedPercentageForObjectIdsIsNumberBetween0IncAnd100Exc($objectIds)
    {
        $hashing = new Hashing();
        $result = $hashing->getHashedPercentageForObjectIds($objectIds);
        $this->assertTrue(100 > $result);
        $this->assertTrue(0 <= $result);
    }

    /**
     * @dataProvider objectIds
     */
    public function testGetHashedPercentageForObjectIdsIsTheSameEachTime($objectIds)
    {
        $hashing = new Hashing();
        $result = $hashing->getHashedPercentageForObjectIds($objectIds);
        $result1 = $hashing->getHashedPercentageForObjectIds($objectIds);
        $this->assertEquals($result, $result1);
    }

    public function testGetHashedPercentageForObjectIdsIsTheDifferentForDifferentIdentities()
    {
        $hashing = new Hashing();
        $result = $hashing->getHashedPercentageForObjectIds([14, 106]);
        $result1 = $hashing->getHashedPercentageForObjectIds([53, 200]);
        $this->assertNotEquals($result, $result1);
    }

    public function testGetHashedPercentageForObjectIdsShouldBeEvenlyDistributed()
    {
        $testSample = 500;
        $numTestBuckets = 50;
        $testBucketSize = (int)$testSample / $numTestBuckets;
        $errorFactor = 0.1;

        $samplePair = range(1, $testSample);
        $objectIdPairs = [];
        foreach ($samplePair as $pair) {
            foreach ($samplePair as $pair1) {
                $objectIdPairs[] = [$pair, $pair1];
            }
        }

        $hashingLib = new Hashing();
        $hashedPercentages = array_map(
            fn ($objectPairs) => $hashingLib->getHashedPercentageForObjectIds($objectPairs),
            $objectIdPairs
        );

        sort($hashedPercentages);

        foreach (range(1, $numTestBuckets) as $i) {
            $bucketStart = $i * $testBucketSize;

            $bucketValueLimit = min(
                (($i + 1) / $numTestBuckets + $errorFactor * (($i + 1) / $numTestBuckets)),
                1
            );

            $values = array_slice($hashedPercentages, $bucketStart, $testBucketSize);
            foreach ($values as $value) {
                $this->assertTrue($value <= $bucketValueLimit);
            }
        }
    }
}
