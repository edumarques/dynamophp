<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\ODM;

use Generator;

/**
 * @template T
 */
readonly class ResultStream
{
    /**
     * @param Generator<T> $items
     */
    public function __construct(
        protected Generator $items,
    ) {
    }

    /**
     * @return Generator<T>|array<T>
     */
    public function getItems(bool $asArray = false): iterable
    {
        return $asArray ? iterator_to_array($this->items) : $this->items;
    }
}
