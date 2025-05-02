<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Attribute;

interface KeyInterface
{
    /**
     * @return array<int, string>
     */
    public function getFields(): array;

    public function getName(): string;

    public function getDelimiter(): string;

    public function getPrefix(): ?string;
}
