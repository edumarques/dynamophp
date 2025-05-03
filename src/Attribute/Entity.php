<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Attribute;

use Attribute as PHPAttribute;

#[PHPAttribute(PHPAttribute::TARGET_CLASS)]
class Entity
{
    public function __construct(
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
