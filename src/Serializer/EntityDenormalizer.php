<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Serializer;

use EduardoMarques\DynamoPHP\Metadata\MetadataException;
use EduardoMarques\DynamoPHP\Metadata\MetadataLoader;
use ReflectionException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class EntityDenormalizer
{
    public function __construct(
        protected MetadataLoader $metadataLoader,
        protected DenormalizerInterface $denormalizer,
    ) {
    }

    /**
     * @param array<string, array<mixed, mixed>> $item
     * @param class-string $class
     * @throws ExceptionInterface
     * @throws ReflectionException
     * @throws MetadataException
     */
    public function denormalize(array $item, string $class): object
    {
        return $this->denormalizeAttributes($item, $class);
    }

    /**
     * @param array<string, mixed> $item
     * @param class-string $class
     * @throws ReflectionException
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

        return $this->denormalizer->denormalize($normalizedItem, $class);
    }
}
