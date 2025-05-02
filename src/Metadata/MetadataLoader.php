<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Metadata;

use EduardoMarques\DynamoPHP\Attribute\Attribute;
use EduardoMarques\DynamoPHP\Attribute\Entity;

class MetadataLoader
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $cache = [];

    /**
     * @param class-string $class
     *
     * @throws \ReflectionException
     */
    public function getClassMetadata(string $class): ClassMetadata
    {
        if (isset($this->cache[__METHOD__][$class])) {
            return $this->cache[__METHOD__][$class];
        }

        $reflection = new \ReflectionClass($class);

        $classProperties = $this->getClassProperties($reflection);

        $metadata = new ClassMetadata($classProperties);

        $this->cache[__METHOD__][$class] = $metadata;

        return $metadata;
    }

    /**
     * @param class-string $class
     *
     * @throws \ReflectionException
     * @throws MetadataException
     */
    public function getEntityMetadata(string $class): EntityMetadata
    {
        if (isset($this->cache[__METHOD__][$class])) {
            return $this->cache[__METHOD__][$class];
        }

        $reflection = new \ReflectionClass($class);

        $classAttributes = $this->getClassAttributes($reflection);
        $entityAttribute = $classAttributes[Entity::class] ?? null;

        if (!($entityAttribute instanceof Entity)) {
            throw new MetadataException(sprintf('No %s attribute declared for class "%s"', Entity::class, $class));
        }

        $propertyAttributes = $this->getPropertyAttributes($reflection);
        $attributes = [];

        foreach ($propertyAttributes as $prop => $attrs) {
            foreach ($attrs as $attr) {
                if ($attr instanceof Attribute) {
                    $attributes[$prop] = $attr;
                }
            }
        }

        $metadata = new EntityMetadata($entityAttribute, $attributes);

        $this->cache[__METHOD__][$class] = $metadata;

        return $metadata;
    }

    /**
     * @return array<string, \ReflectionProperty>
     */
    private function getClassProperties(\ReflectionClass $reflection): array
    {
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            $properties[$property->getName()] = $property;
        }

        return $properties;
    }

    /**
     * @return array<class-string, object>
     */
    private function getClassAttributes(\ReflectionClass $reflection): array
    {
        $attributes = [];

        foreach ($reflection->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            $attributes[$instance::class] = $instance;
        }

        return $attributes;
    }

    /**
     * @return array<string, array<int, object>>
     */
    private function getPropertyAttributes(\ReflectionClass $reflection): array
    {
        $attributes = [];

        foreach ($reflection->getProperties() as $property) {
            $propertyAttributes = $property->getAttributes();

            if (!empty($propertyAttributes)) {
                $attributes[$property->getName()] = array_map(
                    fn(\ReflectionAttribute $attribute): object => $attribute->newInstance(),
                    $propertyAttributes,
                );
            }
        }

        return $attributes;
    }
}
