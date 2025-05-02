<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Attribute;

class PartitionKey implements KeyInterface
{
    public function __construct(
        /** @var array<int, string> */
        protected array $fields,
        protected string $name = 'PK',
        protected string $delimiter = '#',
        protected ?string $prefix = null,
    ) {
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
