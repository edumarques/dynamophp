<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\ODM;

use Generator;

class ResultStream
{
    public function __construct(
        protected Generator $items,
    ) {
    }

    /**
     * @return Generator|array<string, mixed>
     */
    public function getItems(bool $asArray = false): iterable
    {
        return $asArray ? iterator_to_array($this->items) : $this->items;
    }
}
