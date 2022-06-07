<?php

use FlagsmithTest\ClientFixtures;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class AnalyticsTest extends TestCase
{
    public function testAnalyticsProcessorTrackFeatureUpdatesAnalyticsData()
    {
        $analyticsProcessor = ClientFixtures::analyticsProcessor();

        $analyticsProcessor->trackFeature(1);
        $this->assertEquals($analyticsProcessor->analytics_data[1], 1);

        $analyticsProcessor->trackFeature(1);
        $this->assertEquals($analyticsProcessor->analytics_data[1], 2);
    }

    public function testAnalyticsProcessorFlushClearsAnalyticsData()
    {
        $analyticsProcessor = ClientFixtures::analyticsProcessor();

        $analyticsProcessor->trackFeature(1);
        $analyticsProcessor->flush();
        $this->assertEquals(count($analyticsProcessor->analytics_data), 0);
    }

    public function testAnalyticsProcessorFlushPostRequestDataMatchAnanlyticsData()
    {
        $client = $this->createMock(ClientInterface::class);

        $analyticsProcessor = ClientFixtures::analyticsProcessor($client);
        $client->expects($this->once())
            ->method('sendRequest');

        $analyticsProcessor->trackFeature(1);
        $analyticsProcessor->flush();
    }

    public function testAnalyticsProcessorFlushEarlyExitIfAnalyticsDataIsEmpty()
    {
        $client = $this->createMock(ClientInterface::class);

        $analyticsProcessor = ClientFixtures::analyticsProcessor($client);
        $client->expects($this->never())
            ->method('sendRequest');

        $analyticsProcessor->flush();
    }

    public function testAnalyticsProcessorCallingTrackFeatureCallsFlushWhenTimerRunsOut()
    {
        $client = $this->createMock(ClientInterface::class);

        $analyticsProcessor = ClientFixtures::analyticsProcessor($client);
        $client->expects($this->once())
            ->method('sendRequest');

        $analyticsProcessor->trackFeature(1);
        unset($analyticsProcessor);
    }
}
