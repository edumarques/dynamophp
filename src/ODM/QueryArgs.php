<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\ODM;

class QueryArgs extends AbstractOpArgs
{
    public function keyConditionExpression(string $expression): static
    {
        $this->args['KeyConditionExpression'] = $expression;

        return $this;
    }

    public function scanIndexForward(bool $asc = true): static
    {
        $this->args['ScanIndexForward'] = $asc;

        return $this;
    }

    /**
     * @inheritdoc
     * @throws OpArgsException
     */
    public function get(): array
    {
        if (!isset($this->args['KeyConditionExpression'])) {
            throw new OpArgsException('KeyConditionExpression is required for query operations.');
        }

        return parent::get();
    }
}
