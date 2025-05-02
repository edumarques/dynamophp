<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Entity
{
    public function __construct(
        /** @var non-empty-string */
        public string $table,
        public KeyInterface $partitionKey,
        public ?KeyInterface $sortKey = null,
    ) {
        if ('' === $this->table) {
            throw new InvalidArgumentException(
                sprintf('Attribute argument %s::table must not be empty', $this::class)
            );
        }
    }
}
