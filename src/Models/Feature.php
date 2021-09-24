<?php

namespace Flagsmith\Models;

use DateTimeInterface;
use Flagsmith\Concerns\HasWith;

class Feature
{
    use HasWith;

    private int $id;
    private string $name;
    private DateTimeInterface $createdDate;
    private string $description;
    private string $initialValue;
    private bool $defaultEnabled;
    private string $type;

    /**
     * Get the value of id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @param int $id
     *
     * @return self
     */
    public function withId(int $id): self
    {
        return $this->with('id', $id);
    }

    /**
     * Get the value of name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @param string $name
     *
     * @return self
     */
    public function withName(string $name): self
    {
        return $this->with('name', $name);
    }

    /**
     * Get the value of createdDate
     *
     * @return DateTimeInterface
     */
    public function getCreatedDate(): DateTimeInterface
    {
        return $this->createdDate;
    }

    /**
     * Set the value of createdDate
     *
     * @param DateTimeInterface $createdDate
     *
     * @return self
     */
    public function withCreatedDate(DateTimeInterface $createdDate): self
    {
        return $this->with('createdDate', $createdDate);
    }

    /**
     * Get the value of description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set the value of description
     *
     * @param string $description
     *
     * @return self
     */
    public function withDescription(string $description): self
    {
        return $this->with('description', $description);
    }

    /**
     * Get the value of initialValue
     *
     * @return string
     */
    public function getInitialValue(): string
    {
        return $this->initialValue;
    }

    /**
     * Set the value of initialValue
     *
     * @param string $initialValue
     *
     * @return self
     */
    public function withInitialValue(string $initialValue): self
    {
        return $this->with('initialValue', $initialValue);
    }

    /**
     * Get the value of defaultEnabled
     *
     * @return string
     */
    public function getDefaultEnabled(): string
    {
        return $this->defaultEnabled;
    }

    /**
     * Set the value of defaultEnabled
     *
     * @param bool $defaultEnabled
     *
     * @return self
     */
    public function withDefaultEnabled(bool $defaultEnabled): self
    {
        return $this->with('defaultEnabled', $defaultEnabled);
    }

    /**
     * Get the value of type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the value of type
     *
     * @param string $type
     *
     * @return self
     */
    public function withType(string $type): self
    {
        return $this->with('type', $type);
    }
}
