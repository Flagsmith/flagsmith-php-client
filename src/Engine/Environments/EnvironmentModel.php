<?php

namespace Flagsmith\Engine\Environments;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Concerns\JsonSerializer;
use Flagsmith\Engine\Environments\Integrations\IntegrationModel;
use Flagsmith\Engine\Projects\ProjectModel;
use Flagsmith\Engine\Utils\Collections\FeatureStateModelList;

#[\AllowDynamicProperties]
class EnvironmentModel
{
    use HasWith;
    use JsonSerializer;
    public int $id;
    public string $api_key;
    public FeatureStateModelList $feature_states;
    public IntegrationModel $segment_config;
    public IntegrationModel $heap_config;
    public IntegrationModel $mixpanel_config;
    public IntegrationModel $amplitude_config;
    public ProjectModel $project;
    private array $keys = [
        'feature_states' => 'Flagsmith\Engine\Utils\Collections\FeatureStateModelList',
        'segment_config' => 'Flagsmith\Engine\Environments\Integrations\IntegrationModel',
        'heap_config' => 'Flagsmith\Engine\Environments\Integrations\IntegrationModel',
        'mixpanel_config' => 'Flagsmith\Engine\Environments\Integrations\IntegrationModel',
        'amplitude_config' => 'Flagsmith\Engine\Environments\Integrations\IntegrationModel',
        'project' => 'Flagsmith\Engine\Projects\ProjectModel',
    ];

    public function __construct()
    {
        $this->feature_states = new FeatureStateModelList();
    }

    /**
     * Get Id.
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Build with ID.
     * @param int $id
     * @return EnvironmentModel
     */
    public function withId(int $id): self
    {
        return $this->with('id', $id);
    }

    /**
     * Get API Key.
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->api_key;
    }

    /**
     * Build with API key.
     * @param string $apiKey
     * @return EnvironmentModel
     */
    public function withApiKey(string $apiKey): self
    {
        return $this->with('api_key', $apiKey);
    }
    /**
     * get Project model.
     * @return ProjectModel
     */
    public function getProject(): ProjectModel
    {
        return $this->project;
    }

    /**
     * build with project model.
     * @param ProjectModel $project
     * @return EnvironmentModel
     */
    public function withProject(ProjectModel $project): self
    {
        return $this->with('project', $project);
    }

    /**
     * get Amplitude config.
     * @return IntegrationModel
     */
    public function getAmplitudeConfig(): IntegrationModel
    {
        return $this->amplitude_config;
    }

    /**
     * Build Amplitude config.
     * @param IntegrationModel $amplitudeConfig
     * @return EnvironmentModel
     */
    public function withAmplitudeConfig(IntegrationModel $amplitudeConfig): self
    {
        return $this->with('amplitude_config', $amplitudeConfig);
    }

    /**
     * get segment config.
     * @return IntegrationModel
     */
    public function getSegmentConfig(): IntegrationModel
    {
        return $this->segment_config;
    }

    /**
     * Build with segment config model.
     * @param IntegrationModel $segmentConfig
     * @return EnvironmentModel
     */
    public function withSegmentConfig(IntegrationModel $segmentConfig): self
    {
        return $this->with('segment_config', $segmentConfig);
    }

    /**
     * get mixpanel config.
     * @return IntegrationModel
     */
    public function getMixpanelConfig(): IntegrationModel
    {
        return $this->mixpanel_config;
    }

    /**
     * build with mixpanel config.
     * @param IntegrationModel $mixpanelConfig
     * @return EnvironmentModel
     */
    public function withMixpanelConfig(IntegrationModel $mixpanelConfig): self
    {
        return $this->with('mixpanel_config', $mixpanelConfig);
    }

    /**
     * get heap config.
     * @return IntegrationModel
     */
    public function getHeapConfig(): IntegrationModel
    {
        return $this->heap_config;
    }

    /**
     * build with heap config.
     * @param IntegrationModel $heapConfig
     * @return EnvironmentModel
     */
    public function withHeapConfig(IntegrationModel $heapConfig): self
    {
        return $this->with('heap_config', $heapConfig);
    }

    /**
     * get the feature states.
     * @return FeatureStateModelList
     */
    public function getFeatureStates(): FeatureStateModelList
    {
        return $this->feature_states;
    }

    /**
     * Build with the feature states.
     * @param FeatureStateModelList $featureStates
     * @return EnvironmentModel
     */
    public function withFeatureStates(FeatureStateModelList $featureStates): self
    {
        return $this->with('feature_states', $featureStates);
    }
}
