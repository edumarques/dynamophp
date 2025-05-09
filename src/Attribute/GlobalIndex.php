<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Attribute;

class GlobalIndex extends AbstractIndex
{
    public function __construct(
        string $name,
        public AbstractKey $partitionKey,
        public ?AbstractKey $sortKey = null,
    ) {
        parent::__construct($name);
    }
}
