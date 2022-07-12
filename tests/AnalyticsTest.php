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

        $analyticsProcessor->trackFeature("my_feature");
        $this->assertEquals($analyticsProcessor->analytics_data["my_feature"], 1);

        $analyticsProcessor->trackFeature("my_feature");
        $this->assertEquals($analyticsProcessor->analytics_data["my_feature"], 2);
    }

    public function testAnalyticsProcessorFlushClearsAnalyticsData()
    {
        $analyticsProcessor = ClientFixtures::analyticsProcessor();

        $analyticsProcessor->trackFeature("my_feature");
        $analyticsProcessor->flush();
        $this->assertEquals(count($analyticsProcessor->analytics_data), 0);
    }

    public function testAnalyticsProcessorFlushPostRequestDataMatchAnanlyticsData()
    {
        $client = $this->createMock(ClientInterface::class);

        $analyticsProcessor = ClientFixtures::analyticsProcessor($client);
        $client->expects($this->once())
            ->method('sendRequest');

        $analyticsProcessor->trackFeature("my_feature");
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

        $analyticsProcessor->trackFeature("my_feature");
        unset($analyticsProcessor);
    }
}
