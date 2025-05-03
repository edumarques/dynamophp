<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Serializer;

use Aws\DynamoDb\Marshaler;
use EduardoMarques\DynamoPHP\Metadata\MetadataException;
use EduardoMarques\DynamoPHP\Metadata\MetadataLoader;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

class EntitySerializer
{
    protected Marshaler $marshaler;

    public function __construct(
        protected MetadataLoader $metadataLoader,
        protected SerializerInterface $serializer,
    ) {
        $this->marshaler = new Marshaler();
    }

    /**
     * @return array<string, array<mixed, mixed>>
     * @throws ExceptionInterface
     * @throws MetadataException
     * @throws \ReflectionException
     */
    public function serialize(object $entity, bool $includePrimaryKey = true): array
    {
        $normalized = $this->normalize($entity, $includePrimaryKey);

        return $this->marshaler->marshalItem($normalized);
    }

    /**
     * @param object|class-string $entity
     * @param array<string, mixed> $keyFieldValues
     *
     * @return array<string, string>
     * @throws \ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    public function serializePrimaryKey(object|string $entity, array $keyFieldValues = []): array
    {
        $normalized = $this->normalizePrimaryKey($entity, $keyFieldValues);

        return $this->marshaler->marshalItem($normalized);
    }

    /**
     * @param $item array<string, array<mixed, mixed>>
     *
     * @throws ExceptionInterface
     * @throws \ReflectionException
     * @throws MetadataException
     */
    public function deserialize(array $item, string $class): object
    {
        $normalized = $this->marshaler->unmarshalItem($item);

        return $this->denormalize($normalized, $class);
    }

    /**
     * @return array<string, array<mixed, mixed>>
     * @throws ExceptionInterface
     * @throws \ReflectionException
     * @throws MetadataException
     */
    public function normalize(object $entity, bool $includePrimaryKey = true): array
    {
        $primaryKey = $includePrimaryKey ? $this->normalizePrimaryKey($entity) : [];

        return [
            ...$primaryKey,
            ...$this->normalizeAttributes($entity),
        ];
    }

    /**
     * @param object|class-string $entity
     * @param array<string, mixed> $keyFieldValues
     *
     * @return array<string, string>
     * @throws \ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    public function normalizePrimaryKey(object|string $entity, array $keyFieldValues = []): array
    {
        return [
            ...$this->normalizePartitionKey($entity, $keyFieldValues),
            ...$this->normalizeSortKey($entity, $keyFieldValues),
        ];
    }

    /**
     * @param $item array<string, array<mixed, mixed>>
     *
     * @throws ExceptionInterface
     * @throws \ReflectionException
     * @throws MetadataException
     */
    public function denormalize(array $item, string $class): object
    {
        return $this->denormalizeAttributes($item, $class);
    }

    /**
     * @param object|class-string $entity
     * @param array<string, mixed> $keyFieldValues
     *
     * @return array<string, string>
     * @throws \ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    protected function normalizePartitionKey(object|string $entity, array $keyFieldValues = []): array
    {
        $this->validatePrimaryKeyArguments($entity, $keyFieldValues);
        $isClassString = is_string($entity);
        $class = $isClassString ? $entity : $entity::class;

        $partitionKeyName = $this->normalizePartitionKeyName($class);
        $partitionKeyValue = $isClassString
            ? $this->normalizePartitionKeyValueFromArray($class, $keyFieldValues)
            : $this->normalizePartitionKeyValueFromEntity($entity);

        return [$partitionKeyName => $partitionKeyValue];
    }

    /**
     * @param object|class-string $entity
     * @param array<string, mixed> $keyFieldValues
     *
     * @return array<string, string>
     * @throws \ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    protected function normalizeSortKey(object|string $entity, array $keyFieldValues = []): array
    {
        $this->validatePrimaryKeyArguments($entity, $keyFieldValues);
        $isClassString = is_string($entity);
        $class = $isClassString ? $entity : $entity::class;
        $sortKeyName = $this->normalizeSortKeyName($class);

        $sortKeyValue = $isClassString
            ? $this->normalizeSortKeyValueFromArray($class, $keyFieldValues)
            : $this->normalizeSortKeyValueFromEntity($entity);

        return null === $sortKeyName || null === $sortKeyValue
            ? []
            : [$sortKeyName => $sortKeyValue];
    }

    /**
     * @param object|class-string $entity
     * @param array<string, mixed> $keyFieldValues
     */
    protected function validatePrimaryKeyArguments(object|string $entity, array $keyFieldValues = []): void
    {
        $isClassString = is_string($entity);

        if ($isClassString && false === class_exists($entity)) {
            throw new InvalidEntityException(sprintf('Entity class "%s" does not exist', $entity));
        }

        if ($isClassString && empty($keyFieldValues)) {
            throw new InvalidEntityException('When entity class is provided, fields also need to be.');
        }
    }

    /**
     * @param class-string $class
     *
     * @throws \ReflectionException
     * @throws MetadataException
     */
    protected function normalizePartitionKeyName(string $class): ?string
    {
        $entityMetadata = $this->metadataLoader->getEntityMetadata($class);

        return $entityMetadata->getPartitionKey()->getName();
    }

    /**
     * @param class-string $class
     *
     * @throws \ReflectionException
     * @throws MetadataException
     */
    protected function normalizeSortKeyName(string $class): ?string
    {
        $entityMetadata = $this->metadataLoader->getEntityMetadata($class);

        return $entityMetadata->getSortKey()?->getName();
    }

    /**
     * @throws \ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    protected function normalizePartitionKeyValueFromEntity(object $entity): ?string
    {
        $entityMetadata = $this->metadataLoader->getEntityMetadata($entity::class);
        $key = $entityMetadata->getPartitionKey();
        $definedFields = $key->getFields();
        $delimiter = $key->getDelimiter();
        $prefix = $key->getPrefix();

        $classMetadata = $this->metadataLoader->getClassMetadata($entity::class);
        $finalValue = $prefix;

        foreach ($definedFields as $field) {
            if (false === $classMetadata->offsetExists($field)) {
                throw new InvalidFieldException(
                    sprintf(
                        'Field "%s" defined in Partition Key is invalid. Are you sure it exists in the entity class?',
                        $field
                    )
                );
            }

            $reflectionProperty = $classMetadata->offsetGet($field);
            $propertyValue = $reflectionProperty->getValue($entity);
            $currentFieldValue = $this->serializer->normalize($propertyValue);

            $finalValue .= empty($finalValue) ? $currentFieldValue : $delimiter . $currentFieldValue;
        }

        return $finalValue;
    }

    /**
     * @throws \ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    protected function normalizeSortKeyValueFromEntity(object $entity): ?string
    {
        $entityMetadata = $this->metadataLoader->getEntityMetadata($entity::class);
        $key = $entityMetadata->getSortKey();

        if (null === $key) {
            return null;
        }

        $definedFields = $key->getFields();
        $delimiter = $key->getDelimiter();
        $prefix = $key->getPrefix();

        $classMetadata = $this->metadataLoader->getClassMetadata($entity::class);
        $finalValue = $prefix;

        foreach ($definedFields as $field) {
            if (false === $classMetadata->offsetExists($field)) {
                throw new InvalidFieldException(
                    sprintf(
                        'Field "%s" defined in Sort Key is invalid. Are you sure it exists in the entity class?',
                        $field
                    )
                );
            }

            $reflectionProperty = $classMetadata->offsetGet($field);
            $propertyValue = $reflectionProperty->getValue($entity);
            $currentFieldValue = $this->serializer->normalize($propertyValue);

            $finalValue .= empty($finalValue) ? $currentFieldValue : $delimiter . $currentFieldValue;
        }

        return $finalValue;
    }

    /**
     * @param class-string $class
     * @param array<string, mixed> $valuesByField
     *
     * @throws \ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    protected function normalizePartitionKeyValueFromArray(string $class, array $valuesByField): ?string
    {
        $entityMetadata = $this->metadataLoader->getEntityMetadata($class);
        $key = $entityMetadata->getPartitionKey();
        $definedFields = $key->getFields();
        $delimiter = $key->getDelimiter();
        $prefix = $key->getPrefix();

        $valuesByFieldSorted = [];

        foreach ($definedFields as $field) {
            if (isset($valuesByField[$field])) {
                $valuesByFieldSorted[$field] = $valuesByField[$field];
            }
        }

        $allFieldsProvided = empty(array_diff_key($valuesByFieldSorted, array_flip($definedFields)));

        if (false === $allFieldsProvided) {
            throw new InvalidFieldException(
                'Provided Partition Key fields do not match the ones defined in the entity'
            );
        }

        $classMetadata = $this->metadataLoader->getClassMetadata($class);
        $finalValue = $prefix;

        foreach ($valuesByFieldSorted as $field => $value) {
            if (false === $classMetadata->offsetExists($field)) {
                throw new InvalidFieldException(
                    sprintf('Field "%s" is invalid. Are you sure it exists in the entity class?', $field)
                );
            }

            if (empty($value)) {
                throw new InvalidFieldException(
                    sprintf('Field "%s" is invalid. Are you sure its value is provided?', $field)
                );
            }

            $currentFieldValue = $this->serializer->normalize($value);

            $finalValue .= empty($finalValue) ? $currentFieldValue : $delimiter . $currentFieldValue;
        }

        return $finalValue;
    }

    /**
     * @param class-string $class
     * @param array<string, mixed> $valuesByField
     *
     * @throws \ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    protected function normalizeSortKeyValueFromArray(string $class, array $valuesByField): ?string
    {
        $entityMetadata = $this->metadataLoader->getEntityMetadata($class);
        $key = $entityMetadata->getSortKey();

        if (null === $key) {
            return null;
        }

        $definedFields = $key->getFields();
        $delimiter = $key->getDelimiter();
        $prefix = $key->getPrefix();

        $valuesByFieldSorted = [];

        foreach ($definedFields as $field) {
            if (isset($valuesByField[$field])) {
                $valuesByFieldSorted[$field] = $valuesByField[$field];
            }
        }

        $classMetadata = $this->metadataLoader->getClassMetadata($class);
        $finalValue = $prefix;

        foreach ($valuesByFieldSorted as $field => $value) {
            if (false === $classMetadata->offsetExists($field)) {
                throw new InvalidFieldException(
                    sprintf('Field "%s" is invalid. Are you sure it exists in the entity class?', $field)
                );
            }

            $currentFieldValue = $this->serializer->normalize($value);

            $finalValue .= empty($finalValue) ? $currentFieldValue : $delimiter . $currentFieldValue;
        }

        return $finalValue;
    }

    /**
     * @throws \ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    protected function normalizeAttributes(object $entity): array
    {
        $entityMetadata = $this->metadataLoader->getEntityMetadata($entity::class);
        $classMetadata = $this->metadataLoader->getClassMetadata($entity::class);
        $propertyAttributes = $entityMetadata->getPropertyAttributes();

        $attributes = [];

        foreach ($propertyAttributes as $prop => $attr) {
            $reflectionProperty = $classMetadata->offsetGet($prop);
            $propertyValue = $reflectionProperty?->getValue($entity);
            $attributes[$attr->name ?: $prop] = $this->serializer->normalize($propertyValue);
        }

        return $attributes;
    }

    /**
     * @throws \ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    protected function denormalizeAttributes(array $item, string $class): object
    {
        $entityMetadata = $this->metadataLoader->getEntityMetadata($class);
        $propertyAttributes = $entityMetadata->getPropertyAttributes();

        $normalizedItem = [];

        foreach ($propertyAttributes as $prop => $attr) {
            $normalizedItem[$prop] = $item[$attr->name ?: $prop] ?? null;
        }

        return $this->serializer->denormalize($normalizedItem, $class);
    }
}
