<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Serializer;

use Aws\DynamoDb\Marshaler;
use EduardoMarques\DynamoPHP\Metadata\MetadataException;
use ReflectionException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

readonly class EntitySerializer
{
    protected Marshaler $marshaler;

    public function __construct(
        protected EntityNormalizer $entityNormalizer,
        protected EntityDenormalizer $entityDenormalizer,
    ) {
        $this->marshaler = new Marshaler();
    }

    /**
     * @template T of object
     * @param T $entity
     * @return array<string, array<mixed, mixed>>
     * @throws ExceptionInterface
     * @throws MetadataException
     * @throws ReflectionException
     */
    public function serialize(object $entity, bool $includePrimaryKey = true): array
    {
        $normalizedEntity = $this->entityNormalizer->normalize($entity, $includePrimaryKey);

        return $this->marshaler->marshalItem($normalizedEntity);
    }

    /**
     * @template T of object
     * @param T|class-string<T> $entity
     * @param array<string, mixed> $keyFieldValues
     * @return array<string, string>
     * @throws ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    public function serializePrimaryKey(object|string $entity, array $keyFieldValues = []): array
    {
        $normalizedEntity = $this->entityNormalizer->normalizePrimaryKey($entity, $keyFieldValues);

        return $this->marshaler->marshalItem($normalizedEntity);
    }

    /**
     * @template T of object
     * @param array<string, array<mixed, mixed>> $data
     * @param class-string<T> $class
     * @throws ExceptionInterface
     * @throws ReflectionException
     * @throws MetadataException
     */
    public function deserialize(array $data, string $class): object
    {
        /** @var array<string, mixed> $normalizedData */
        $normalizedData = $this->marshaler->unmarshalItem($data);

        return $this->entityDenormalizer->denormalize($normalizedData, $class);
    }
}
