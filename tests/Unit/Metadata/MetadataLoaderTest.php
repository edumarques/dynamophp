<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Tests\Unit\Metadata;

use EduardoMarques\DynamoPHP\Attribute\Attribute;
use EduardoMarques\DynamoPHP\Metadata\MetadataException;
use EduardoMarques\DynamoPHP\Metadata\MetadataLoader;
use EduardoMarques\DynamoPHP\Tests\Unit\Stubs\ClassA;
use EduardoMarques\DynamoPHP\Tests\Unit\Stubs\EntityA;
use EduardoMarques\DynamoPHP\Tests\Unit\Stubs\EntityB;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class MetadataLoaderTest extends TestCase
{
    private MetadataLoader $metadataLoader;

    #[Test]
    public function itReturnsClassMetadata(): void
    {
        $metadata = $this->metadataLoader->getClassMetadata(ClassA::class);

        $this->assertTrue($metadata->has('id'));
        $this->assertInstanceOf(ReflectionProperty::class, $metadata->get('id'));

        $this->assertTrue($metadata->has('name'));
        $this->assertInstanceOf(ReflectionProperty::class, $metadata->get('name'));
    }

    #[Test]
    public function itReturnsEntityMetadata(): void
    {
        $metadata = $this->metadataLoader->getEntityMetadata(EntityA::class);

        $this->assertSame('tests', $metadata->getTable());
        $this->assertSame(['id'], $metadata->getPartitionKey()->getFields());
        $this->assertSame(['creationDate'], $metadata->getSortKey()?->getFields());

        $propertyAttributes = $metadata->getPropertyAttributes();
        $properties = array_keys($propertyAttributes);
        $attributes = array_values($propertyAttributes);

        $this->assertSame(['id', 'name', 'creationDate', 'cardNumber'], $properties);
        $this->assertInstanceOf(Attribute::class, $attributes[0]);
        $this->assertInstanceOf(Attribute::class, $attributes[1]);
        $this->assertInstanceOf(Attribute::class, $attributes[2]);
        $this->assertInstanceOf(Attribute::class, $attributes[3]);

        $this->assertNull($propertyAttributes['name']->name);
        $this->assertSame('createdAt', $propertyAttributes['creationDate']->name);
    }

    #[Test]
    public function itReturnsEntityMetadataMergedWithParent(): void
    {
        $metadata = $this->metadataLoader->getEntityMetadata(EntityB::class);

        $this->assertSame('tests', $metadata->getTable());
        $this->assertSame(['id'], $metadata->getPartitionKey()->getFields());
        $this->assertSame(['creationDate'], $metadata->getSortKey()?->getFields());

        $propertyAttributes = $metadata->getPropertyAttributes();
        $properties = array_keys($propertyAttributes);
        $attributes = array_values($propertyAttributes);

        $this->assertSame(['id', 'name', 'creationDate', 'cardNumber', 'type'], $properties);
        $this->assertInstanceOf(Attribute::class, $attributes[0]);
        $this->assertInstanceOf(Attribute::class, $attributes[1]);
        $this->assertInstanceOf(Attribute::class, $attributes[2]);
        $this->assertInstanceOf(Attribute::class, $attributes[3]);

        $this->assertSame('fullName', $propertyAttributes['name']->name);
        $this->assertNull($propertyAttributes['creationDate']->name);
    }

    #[Test]
    public function itThrowsExceptionWhenEntityAttributeIsMissing(): void
    {
        $this->expectException(MetadataException::class);
        $this->expectExceptionMessageMatches('/^No .*Entity attribute declared for class .*/');

        $this->metadataLoader->getEntityMetadata(ClassA::class);
    }

    #[Test]
    public function itCachesMetadata(): void
    {
        $classMetadata1 = $this->metadataLoader->getClassMetadata(ClassA::class);
        $classMetadata2 = $this->metadataLoader->getClassMetadata(ClassA::class);

        $this->assertSame($classMetadata2, $classMetadata1);

        $entityMetadata1 = $this->metadataLoader->getEntityMetadata(EntityA::class);
        $entityMetadata2 = $this->metadataLoader->getEntityMetadata(EntityA::class);

        $this->assertSame($entityMetadata2, $entityMetadata1);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->metadataLoader = new MetadataLoader();
    }
}
