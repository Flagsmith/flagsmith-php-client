<?php

namespace Flagsmith\Models;

use Flagsmith\Concerns\HasWith;

/**
 * The running experiment a flag was evaluated under. Only remote identity evaluations carry it.
 */
class Experiment
{
    use HasWith;

    public int $id;
    public string $name;
    public bool $in_experiment;

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
     * @return Experiment
     */
    public function withId(int $id): self
    {
        return $this->with('id', $id);
    }

    /**
     * Get the name of the experiment.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Build with Name.
     * @param string $name
     * @return Experiment
     */
    public function withName(string $name): self
    {
        return $this->with('name', $name);
    }

    /**
     * Whether the identity is enrolled in the experiment.
     * @return bool
     */
    public function getInExperiment(): bool
    {
        return $this->in_experiment;
    }

    /**
     * Build with whether the identity is enrolled in the experiment.
     * @param bool $inExperiment
     * @return Experiment
     */
    public function withInExperiment(bool $inExperiment): self
    {
        return $this->with('in_experiment', $inExperiment);
    }

    /**
     * Build experiment from a flag's API metadata, if it carries one.
     * @param object|null $metadata
     * @return Experiment|null
     */
    public static function fromApiMetadata(?object $metadata): ?self
    {
        $experiment = $metadata->experiment ?? null;
        if ($experiment === null) {
            return null;
        }

        return (new self())
            ->withId($experiment->id)
            ->withName($experiment->name)
            ->withInExperiment($experiment->in_experiment);
    }
}
