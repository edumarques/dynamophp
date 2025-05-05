<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Tests\Unit\Attribute;

use EduardoMarques\DynamoPHP\Attribute\InvalidArgumentException;
use EduardoMarques\DynamoPHP\Attribute\PartitionKey;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PartitionKeyTest extends TestCase
{
    #[Test]
    public function itReturnsFieldsSanitized(): void
    {
        $partitionKey = new PartitionKey(['id', 'id', 'name']);

        $this->assertSame(['id', 'name'], $partitionKey->getFields());
    }

    #[Test]
    public function itThrowsExceptionWhenNoFieldsAreProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^Attribute argument .*PartitionKey::fields must not be empty/');

        new PartitionKey([]);
    }
}
