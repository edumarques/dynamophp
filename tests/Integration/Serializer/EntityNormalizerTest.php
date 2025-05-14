<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Tests\Integration\Serializer;

use DateTime;
use EduardoMarques\DynamoPHP\Metadata\MetadataLoader;
use EduardoMarques\DynamoPHP\Serializer\EntityNormalizer;
use EduardoMarques\DynamoPHP\Tests\Integration\Stubs\EntityA;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class EntityNormalizerTest extends TestCase
{
    private MetadataLoader $metadataLoader;

    private Serializer $serializer;

    private EntityNormalizer $entityNormalizer;

    #[Test]
    public function itReturnsNormalizedEntity(): void
    {
        $id = '8798b91f-fe8e-498c-8145-c757029346ef';
        $name = 'John Doe';
        $creationDate = new DateTime();
        $entity = new EntityA($id);
        $entity->name = $name;
        $entity->creationDate = $creationDate;

        $expected = [
            'PK' => $id,
            'SK' => $creationDate->format('c'),
            'name' => $name,
            'type' => 'A',
            'createdAt' => $creationDate->format('c'),
            'birthDate' => null,
            'id' => $id,
        ];

        $this->assertSame($expected, $this->entityNormalizer->normalize($entity));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->metadataLoader = new MetadataLoader();

        $this->serializer = new Serializer([
            new BackedEnumNormalizer(),
            new DateTimeNormalizer(),
            new ObjectNormalizer(),
        ]);

        $this->entityNormalizer = new EntityNormalizer($this->metadataLoader, $this->serializer);
    }
}
