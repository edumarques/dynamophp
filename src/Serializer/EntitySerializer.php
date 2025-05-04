<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Serializer;

use Aws\DynamoDb\Marshaler;
use EduardoMarques\DynamoPHP\Metadata\MetadataException;
use ReflectionException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class EntitySerializer
{
    protected Marshaler $marshaler;

    public function __construct(
        protected EntityNormalizer $entityNormalizer,
        protected EntityDenormalizer $entityDenormalizer,
    ) {
        $this->marshaler = new Marshaler();
    }

    /**
     * @return array<string, array<mixed, mixed>>
     * @throws ExceptionInterface
     * @throws MetadataException
     * @throws ReflectionException
     */
    public function serialize(object $entity, bool $includePrimaryKey = true): array
    {
        $normalized = $this->entityNormalizer->normalize($entity, $includePrimaryKey);

        return $this->marshaler->marshalItem($normalized);
    }

    /**
     * @param object|class-string $entity
     * @param array<string, mixed> $keyFieldValues
     * @return array<string, string>
     * @throws ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    public function serializePrimaryKey(object|string $entity, array $keyFieldValues = []): array
    {
        $normalized = $this->entityNormalizer->normalizePrimaryKey($entity, $keyFieldValues);

        return $this->marshaler->marshalItem($normalized);
    }

    /**
     * @param array<string, array<mixed, mixed>> $item
     * @param class-string $class
     * @throws ExceptionInterface
     * @throws ReflectionException
     * @throws MetadataException
     */
    public function deserialize(array $item, string $class): object
    {
        /** @var array<string, mixed> $normalized */
        $normalized = $this->marshaler->unmarshalItem($item);

        return $this->entityDenormalizer->denormalize($normalized, $class);
    }
}
