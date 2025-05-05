<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Tests\Unit\Attribute;

use EduardoMarques\DynamoPHP\Attribute\Entity;
use EduardoMarques\DynamoPHP\Attribute\InvalidArgumentException;
use EduardoMarques\DynamoPHP\Attribute\PartitionKey;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EntityTest extends TestCase
{
    #[Test]
    public function itThrowsExceptionWhenTableNameIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^Attribute argument .*Entity::table must not be empty/');

        new Entity('', new PartitionKey(['id']));
    }
}
