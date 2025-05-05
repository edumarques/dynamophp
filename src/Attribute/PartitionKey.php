<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Attribute;

final class PartitionKey extends AbstractKey
{
    /**
     * @param array<int, string> $fields
     */
    public function __construct(
        array $fields,
        string $name = 'PK',
        string $delimiter = '#',
        ?string $prefix = null,
    ) {
        parent::__construct($fields, $name, $delimiter, $prefix);
    }
}
