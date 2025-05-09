<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Metadata;

use EduardoMarques\DynamoPHP\Attribute\AbstractIndex;
use EduardoMarques\DynamoPHP\Attribute\AbstractKey;
use EduardoMarques\DynamoPHP\Attribute\Attribute;
use EduardoMarques\DynamoPHP\Attribute\Entity;

final readonly class EntityMetadata
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

    public function getPartitionKey(): AbstractKey
    {
        return $this->entityAttribute->partitionKey;
    }

    public function getSortKey(): ?AbstractKey
    {
        return $this->entityAttribute->sortKey;
    }

    /**
     * @return array<int, AbstractIndex>
     */
    public function getIndexes(): array
    {
        return $this->entityAttribute->indexes;
    }

    /**
     * @return array<string, Attribute>
     */
    public function getPropertyAttributes(): array
    {
        return $this->propertyAttributes;
    }
}
