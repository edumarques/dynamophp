<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Attribute;

class LocalIndex extends AbstractIndex
{
    public function __construct(
        string $name,
        public AbstractKey $sortKey,
    ) {
        parent::__construct($name);
    }
}
