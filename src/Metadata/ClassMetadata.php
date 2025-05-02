<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Metadata;

class ClassMetadata implements \ArrayAccess
{
    public function __construct(
        /** @var array<string, \ReflectionProperty> */
        protected array $properties,
    ) {
    }

    /**
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->properties[$offset]);
    }

    /**
     * @param string $offset
     */
    public function offsetGet(mixed $offset): ?\ReflectionProperty
    {
        return $this->properties[$offset] ?? null;
    }

    /**
     * @param string $offset
     * @param \ReflectionProperty $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->properties[$offset] = $value;
    }

    /**
     * @param string $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->properties[$offset]);
    }

}
