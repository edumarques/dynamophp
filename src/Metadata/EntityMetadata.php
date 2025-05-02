<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Metadata;

use EduardoMarques\DynamoPHP\Attribute\Attribute;
use EduardoMarques\DynamoPHP\Attribute\Entity;
use EduardoMarques\DynamoPHP\Attribute\KeyInterface;

class EntityMetadata
{
    public function __construct(
        protected Entity $entityAttribute,
        /** @var array<string, Attribute> */
        protected array $propertyAttributes,
    ) {
    }

    public function getTable(): string
    {
        return $this->entityAttribute->table;
    }

    public function getPartitionKey(): KeyInterface
    {
        return $this->entityAttribute->partitionKey;
    }

    public function getSortKey(): ?KeyInterface
    {
        return $this->entityAttribute->sortKey;
    }

    /**
     * @return array<string, Attribute>
     */
    public function getPropertyAttributes(): array
    {
        return $this->propertyAttributes;
    }
}
