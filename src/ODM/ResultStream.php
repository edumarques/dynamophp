<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\ODM;

class ResultStream
{
    public function __construct(
        protected \Generator $items,
    ) {
    }

    public function getItems(bool $asArray = false): iterable
    {
        return $asArray ? iterator_to_array($this->items) : $this->items;
    }
}
