<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\ODM;

use Aws\DynamoDb\Marshaler;

abstract class AbstractBuilder
{
    protected Marshaler $marshaler;

    /** @var array<string, mixed> */
    protected array $parameters = [];

    public function __construct()
    {
        $this->marshaler = new Marshaler();
    }

    public function filterExpression(string $expression): self
    {
        $this->parameters['FilterExpression'] = $expression;

        return $this;
    }

    public function projectionExpression(string $expression): self
    {
        $this->parameters['ProjectionExpression'] = $expression;

        return $this;
    }

    /**
     * @param array<string, string> $names
     */
    public function expressionAttributeNames(array $names): self
    {
        $this->parameters['ExpressionAttributeNames'] = $names;

        return $this;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function expressionAttributeValues(array $values): self
    {
        $this->parameters['ExpressionAttributeValues'] = $this->serialize($values);

        return $this;
    }

    public function limit(int $limit): self
    {
        if (0 < $limit) {
            $this->parameters['Limit'] = $limit;
        }

        return $this;
    }

    public function select(string $select): self
    {
        $this->parameters['Select'] = $select;

        return $this;
    }

    public function consistentRead(bool $value = true): self
    {
        $this->parameters['ConsistentRead'] = $value;

        return $this;
    }

    public function returnConsumedCapacity(string $value): self
    {
        $this->parameters['ReturnConsumedCapacity'] = $value;

        return $this;
    }

    /**
     * @param array<string, mixed> $startKey
     */
    public function exclusiveStartKey(array $startKey): self
    {
        $this->parameters['ExclusiveStartKey'] = $startKey;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    abstract public function build(): array;

    /**
     * @param array<string, mixed> $values
     * @return array<string, array<string, mixed>>
     */
    protected function serialize(array $values): array
    {
        $marshaled = [];

        foreach ($values as $key => $value) {
            $marshaled[$key] = $this->marshaler->marshalValue($value);
        }

        return $marshaled;
    }
}
