<?php

namespace Flagsmith\Engine\Projects;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Concerns\JsonSerializer;
use Flagsmith\Engine\Organisations\OrganisationModel;
use Flagsmith\Engine\Utils\Collections\SegmentModelList;

class ProjectModel
{
    use HasWith;
    use JsonSerializer;

    public int $id;
    public string $name;
    public OrganisationModel $organisation;
    public bool $hide_disabled_flags;
    public SegmentModelList $segments;
    private array $keys = [
        'organisation' => 'Flagsmith\Engine\Organisations\OrganisationModel',
        'segments' => 'Flagsmith\Engine\Utils\Collections\SegmentModelList'
    ];

    public function __construct()
    {
        $this->segments = new SegmentModelList();
    }

    /**
     * Get the segments
     * @return SegmentModelList
     */
    public function getSegments(): SegmentModelList
    {
        return $this->segments;
    }

    /**
     * Build with segments.
     * @param SegmentModelList $segments
     * @return ProjectModel
     */
    public function withSegments(SegmentModelList $segments): self
    {
        return $this->with('segments', $segments);
    }

    /**
     * Get the hide disabled flags bool.
     * @return bool
     */
    public function getHideDisabledFlags(): bool
    {
        return $this->hide_disabled_flags;
    }

    /**
     * Build with the hide disabled flags bool.
     * @param bool $hideDisabledFlags
     * @return ProjectModel
     */
    public function withHideDisabledFlags(bool $hideDisabledFlags): self
    {
        return $this->with('hide_disabled_flags', $hideDisabledFlags);
    }

    /**
     * Get the organisation model.
     * @return OrganisationModel
     */
    public function getOrganisation(): OrganisationModel
    {
        return $this->organisation;
    }

    /**
     * Build with organisation model.
     * @param OrganisationModel $organisation
     * @return ProjectModel
     */
    public function withOrganisation(OrganisationModel $organisation): self
    {
        return $this->with('organisation', $organisation);
    }

    /**
     * Get the name.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Build with name.
     * @param string $name
     * @return ProjectModel
     */
    public function withName(string $name): self
    {
        return $this->with('name', $name);
    }

    /**
     * Get the ID.
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Build with ID.
     * @param int $id
     * @return ProjectModel
     */
    public function withId(int $id): self
    {
        return $this->with('id', $id);
    }
}
