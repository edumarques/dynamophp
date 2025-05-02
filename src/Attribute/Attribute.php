<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Attribute
{
    public function __construct(
        public ?string $name = null,
    ) {
    }
}
