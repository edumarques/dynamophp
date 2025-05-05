<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Serializer;

use EduardoMarques\DynamoPHP\Metadata\MetadataException;
use EduardoMarques\DynamoPHP\Metadata\MetadataLoader;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class EntityNormalizer
{
    public const string DATETIME_FORMAT_KEY = EntityNormalizer::class . '_datetime_format';

    public function __construct(
        protected MetadataLoader $metadataLoader,
        protected NormalizerInterface $normalizer,
    ) {
    }

    /**
     * @template T of object
     * @param T $entity
     * @return array<string, mixed>
     * @throws ExceptionInterface
     * @throws ReflectionException
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
     * @template T of object
     * @param T|class-string<T> $entity
     * @param array<string, mixed> $keyFieldValues
     * @return array<string, string>
     * @throws ReflectionException
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
     * @template T of object
     * @param T|class-string<T> $entity
     * @param array<string, mixed> $keyFieldValues
     * @return array<string, string>
     * @throws ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    protected function normalizePartitionKey(object|string $entity, array $keyFieldValues = []): array
    {
        $this->validateKeyArguments($entity, $keyFieldValues);
        $isClassString = is_string($entity);
        $class = $isClassString ? $entity : $entity::class;

        $partitionKeyName = $this->normalizePartitionKeyName($class);
        $partitionKeyValue = $isClassString
            ? $this->normalizePartitionKeyValueFromArray($class, $keyFieldValues)
            : $this->normalizePartitionKeyValueFromEntity($entity);

        return [$partitionKeyName => $partitionKeyValue];
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
    protected function normalizeSortKey(object|string $entity, array $keyFieldValues = []): array
    {
        $this->validateKeyArguments($entity, $keyFieldValues);
        $isClassString = is_string($entity);
        $class = $isClassString ? $entity : $entity::class;
        $sortKeyName = $this->normalizeSortKeyName($class);

        $sortKeyValue = $isClassString
            ? $this->normalizeSortKeyValueFromArray($class, $keyFieldValues)
            : $this->normalizeSortKeyValueFromEntity($entity);

        return empty($sortKeyName) || empty($sortKeyValue)
            ? []
            : [$sortKeyName => $sortKeyValue];
    }

    /**
     * @template T of object
     * @param T|class-string<T> $entity
     * @param array<string, mixed> $keyFieldValues
     */
    protected function validateKeyArguments(object|string $entity, array $keyFieldValues = []): void
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
     * @template T of object
     * @param class-string<T> $class
     * @throws ReflectionException
     * @throws MetadataException
     */
    protected function normalizePartitionKeyName(string $class): string
    {
        $entityMetadata = $this->metadataLoader->getEntityMetadata($class);

        return $entityMetadata->getPartitionKey()->getName();
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @throws ReflectionException
     * @throws MetadataException
     */
    protected function normalizeSortKeyName(string $class): ?string
    {
        $entityMetadata = $this->metadataLoader->getEntityMetadata($class);

        return $entityMetadata->getSortKey()?->getName();
    }

    /**
     * @template T of object
     * @param T $entity
     * @throws ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    protected function normalizePartitionKeyValueFromEntity(object $entity): string
    {
        $entityMetadata = $this->metadataLoader->getEntityMetadata($entity::class);
        $key = $entityMetadata->getPartitionKey();
        $definedFields = $key->getFields();
        $delimiter = $key->getDelimiter();
        $prefix = $key->getPrefix();

        $classMetadata = $this->metadataLoader->getClassMetadata($entity::class);
        $finalValue = $prefix ?? '';

        foreach ($definedFields as $field) {
            if (false === $classMetadata->has($field)) {
                throw new InvalidFieldException(
                    sprintf(
                        'Field "%s" defined in Partition Key is invalid. Are you sure it exists in the entity class?',
                        $field
                    )
                );
            }

            /** @var ReflectionProperty $reflectionProperty */
            $reflectionProperty = $classMetadata->get($field);
            $propertyValue = $reflectionProperty->getValue($entity);

            /** @var scalar $currentFieldValue */
            $currentFieldValue = $this->normalizer->normalize($propertyValue);

            $finalValue .= empty($finalValue) ? $currentFieldValue : $delimiter . $currentFieldValue;
        }

        return $finalValue;
    }

    /**
     * @template T of object
     * @param T $entity
     * @throws ReflectionException
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
        $finalValue = $prefix ?? '';

        foreach ($definedFields as $field) {
            if (false === $classMetadata->has($field)) {
                throw new InvalidFieldException(
                    sprintf(
                        'Field "%s" defined in Sort Key is invalid. Are you sure it exists in the entity class?',
                        $field
                    )
                );
            }

            /** @var ReflectionProperty $reflectionProperty */
            $reflectionProperty = $classMetadata->get($field);
            $propertyValue = $reflectionProperty->getValue($entity);

            /** @var scalar $currentFieldValue */
            $currentFieldValue = $this->normalizer->normalize($propertyValue);

            $finalValue .= empty($finalValue) ? $currentFieldValue : $delimiter . $currentFieldValue;
        }

        return $finalValue;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param array<string, mixed> $valuesByField
     * @throws ReflectionException
     * @throws ExceptionInterface
     * @throws MetadataException
     */
    protected function normalizePartitionKeyValueFromArray(string $class, array $valuesByField): string
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

        $allFieldsProvided = empty(array_diff_key(array_flip($definedFields), $valuesByFieldSorted));

        if (false === $allFieldsProvided) {
            throw new InvalidFieldException(
                'Provided Partition Key fields do not match the ones defined in the entity'
            );
        }

        $classMetadata = $this->metadataLoader->getClassMetadata($class);
        $finalValue = $prefix ?? '';

        foreach ($valuesByFieldSorted as $field => $value) {
            if (false === $classMetadata->has($field)) {
                throw new InvalidFieldException(
                    sprintf('Field "%s" is invalid. Are you sure it exists in the entity class?', $field)
                );
            }

            if (empty($value)) {
                throw new InvalidFieldException(
                    sprintf('Field "%s" is invalid. Are you sure its value is provided?', $field)
                );
            }

            /** @var scalar $currentFieldValue */
            $currentFieldValue = $this->normalizer->normalize($value);

            $finalValue .= empty($finalValue) ? $currentFieldValue : $delimiter . $currentFieldValue;
        }

        return $finalValue;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param array<string, mixed> $valuesByField
     * @throws ReflectionException
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

        $allFieldsProvided = empty(array_diff_key(array_flip($definedFields), $valuesByFieldSorted));

        if (false === $allFieldsProvided) {
            throw new InvalidFieldException(
                'Provided Sort Key fields do not match the ones defined in the entity'
            );
        }

        $classMetadata = $this->metadataLoader->getClassMetadata($class);
        $finalValue = $prefix ?? '';

        foreach ($valuesByFieldSorted as $field => $value) {
            if (false === $classMetadata->has($field)) {
                throw new InvalidFieldException(
                    sprintf('Field "%s" is invalid. Are you sure it exists in the entity class?', $field)
                );
            }

            /** @var scalar $currentFieldValue */
            $currentFieldValue = $this->normalizer->normalize($value);

            $finalValue .= empty($finalValue) ? $currentFieldValue : $delimiter . $currentFieldValue;
        }

        return $finalValue;
    }

    /**
     * @template T of object
     * @param T $entity
     * @return array<string, mixed>
     * @throws ReflectionException
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
            $reflectionProperty = $classMetadata->get($prop);
            $propertyValue = $reflectionProperty?->getValue($entity);
            $attributes[$attr->name ?: $prop] = $this->normalizer->normalize($propertyValue);
        }

        return $attributes;
    }
}
