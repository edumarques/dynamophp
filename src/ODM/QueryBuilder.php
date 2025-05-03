<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\ODM;

class QueryBuilder extends AbstractBuilder
{
    public function indexName(string $index): self
    {
        $this->parameters['IndexName'] = $index;

        return $this;
    }

    public function keyConditionExpression(string $expression): self
    {
        $this->parameters['KeyConditionExpression'] = $expression;

        return $this;
    }

    public function scanIndexForward(bool $asc = true): self
    {
        $this->parameters['ScanIndexForward'] = $asc;

        return $this;
    }

    /**
     * @inheritdoc
     * @throws BuilderException
     */
    public function build(): array
    {
        if (!isset($this->parameters['KeyConditionExpression'])) {
            throw new BuilderException('KeyConditionExpression is required for query operations.');
        }

        return $this->parameters;
    }
}
