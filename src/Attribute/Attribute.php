<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Attribute;

use Attribute as PHPAttribute;

#[PHPAttribute(PHPAttribute::TARGET_PROPERTY)]
class Attribute
{
    public function __construct(
        public ?string $name = null,
        public bool $ignoreIfNull = true,
    ) {
    }
}
