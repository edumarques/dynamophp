<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Tests\Integration\Stubs;

use EduardoMarques\DynamoPHP\Attribute\Attribute;
use EduardoMarques\DynamoPHP\Attribute\Entity;
use EduardoMarques\DynamoPHP\Attribute\PartitionKey;

#[Entity(
    table: 'tests',
    partitionKey: new PartitionKey(['id']),
)]
final class EntityB
{
    #[Attribute]
    public int $id;
}
