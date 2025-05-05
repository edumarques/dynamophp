<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Tests\Unit\Attribute;

use EduardoMarques\DynamoPHP\Attribute\InvalidArgumentException;
use EduardoMarques\DynamoPHP\Attribute\SortKey;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SortKeyTest extends TestCase
{
    #[Test]
    public function itReturnsFieldsSanitized(): void
    {
        $partitionKey = new SortKey(['id' => 'id', 'id', 'name']);

        $this->assertSame(['id', 'name'], $partitionKey->getFields());
    }

    #[Test]
    public function itThrowsExceptionWhenNoFieldsAreProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^Attribute argument .*SortKey::fields must not be empty/');

        new SortKey([]);
    }
}
