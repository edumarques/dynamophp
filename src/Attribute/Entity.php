<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Attribute;

use Attribute as PHPAttribute;

#[PHPAttribute(PHPAttribute::TARGET_CLASS)]
class Entity
{
    /**
     * @param array<int, AbstractIndex> $indexes
     */
    public function __construct(
        public string $table,
        public AbstractKey $partitionKey,
        public ?AbstractKey $sortKey = null,
        public array $indexes = [],
    ) {
        if ('' === $this->table) {
            throw new InvalidArgumentException(
                sprintf('Attribute argument %s::table must not be empty', $this::class)
            );
        }
    }
}
