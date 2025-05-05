<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Attribute;

abstract class AbstractKey
{
    public function __construct(
        /** @var array<int, string> */
        protected array $fields,
        protected string $name,
        protected string $delimiter,
        protected ?string $prefix = null,
    ) {
        if (empty($this->fields)) {
            throw new InvalidArgumentException(
                sprintf('Attribute argument %s::fields must not be empty', $this::class)
            );
        }

        $this->fields = array_values(array_unique($this->fields));
    }

    /**
     * @return array<int, string>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }
}
