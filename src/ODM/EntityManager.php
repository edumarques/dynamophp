<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\ODM;

use Aws\DynamoDb\DynamoDbClient;
use EduardoMarques\DynamoPHP\Metadata\MetadataLoader;
use EduardoMarques\DynamoPHP\Serializer\EntitySerializer;
use Generator;
use Throwable;

class EntityManager
{
    public function __construct(
        protected DynamoDbClient $dynamoDbClient,
        protected MetadataLoader $metadataLoader,
        protected EntitySerializer $entitySerializer,
    ) {
    }

    /**
     * @param class-string $class
     * @param array<string, mixed> $keyFieldValues
     * @throws EntityManagerException
     */
    public function find(string $class, array $keyFieldValues): ?object
    {
        try {
            $key = $this->entitySerializer->serializePrimaryKey($class, $keyFieldValues);

            if (2 > count($key)) {
                throw new EntityManagerException('Fields of both Partition and Sort keys must be provided');
            }

            $table = $this->metadataLoader->getEntityMetadata($class)->getTable();

            $result = $this->dynamoDbClient->getItem(
                [
                    'TableName' => $table,
                    'Key' => $key,
                ]
            );

            $rawItem = $result['Item'] ?? null;

            if (null === $rawItem) {
                return null;
            }

            return $this->entitySerializer->deserialize($rawItem, $class);
        } catch (Throwable $exception) {
            $this->wrapException($exception);
        }
    }

    /**
     * @param class-string $class
     * @throws EntityManagerException
     */
    public function queryOne(string $class, QueryBuilder $queryBuilder): ?object
    {
        $queryBuilder->limit(1);

        /** @var array<int, object> $result */
        $result = $this->query($class, $queryBuilder)->getItems(true);

        return $result[0] ?? null;
    }

    /**
     * @param class-string $class
     * @throws EntityManagerException
     */
    public function query(string $class, QueryBuilder $queryBuilder): ResultStream
    {
        $items = (function () use ($class, $queryBuilder): Generator {
            try {
                $table = $this->metadataLoader->getEntityMetadata($class)->getTable();

                $params = $queryBuilder->build();
                $params['TableName'] = $table;
                $remainingLimit = $params['Limit'] ?? null;

                do {
                    if (null !== $remainingLimit) {
                        $params['Limit'] = $remainingLimit;
                    }

                    $result = $this->dynamoDbClient->query($params);

                    foreach ($result->get('Items') ?? [] as $item) {
                        yield $this->entitySerializer->deserialize($item, $class);

                        if (null === $remainingLimit) {
                            continue;
                        }

                        if (0 >= --$remainingLimit) {
                            return;
                        }
                    }

                    $params['ExclusiveStartKey'] = $result->get('LastEvaluatedKey') ?? null;
                } while (!empty($params['ExclusiveStartKey']));
            } catch (Throwable $exception) {
                $this->wrapException($exception);
            }
        })();

        return new ResultStream($items);
    }

    /**
     * @param class-string $class
     * @throws EntityManagerException
     */
    public function scan(string $class, ScanBuilder $scanBuilder): ResultStream
    {
        $items = (function () use ($class, $scanBuilder): Generator {
            try {
                $table = $this->metadataLoader->getEntityMetadata($class)->getTable();

                $params = $scanBuilder->build();
                $params['TableName'] = $table;
                $remainingLimit = $params['Limit'] ?? null;

                do {
                    if (null !== $remainingLimit) {
                        $params['Limit'] = $remainingLimit;
                    }

                    $result = $this->dynamoDbClient->scan($params);

                    foreach ($result->get('Items') ?? [] as $item) {
                        yield $this->entitySerializer->deserialize($item, $class);

                        if (null === $remainingLimit) {
                            continue;
                        }

                        if (0 >= --$remainingLimit) {
                            return;
                        }
                    }

                    $params['ExclusiveStartKey'] = $result->get('LastEvaluatedKey') ?? null;
                } while (!empty($params['ExclusiveStartKey']));
            } catch (Throwable $exception) {
                $this->wrapException($exception);
            }
        })();

        return new ResultStream($items);
    }

    /**
     * @throws EntityManagerException
     */
    public function save(object $entity): void
    {
        try {
            $table = $this->metadataLoader->getEntityMetadata($entity::class)->getTable();
            $item = $this->entitySerializer->serialize($entity);

            $this->dynamoDbClient->putItem(
                [
                    'TableName' => $table,
                    'Item' => $item,
                ]
            );
        } catch (Throwable $exception) {
            $this->wrapException($exception);
        }
    }

    /**
     * @throws EntityManagerException
     */
    public function remove(object $entity): void
    {
        try {
            $table = $this->metadataLoader->getEntityMetadata($entity::class)->getTable();
            $key = $this->entitySerializer->serializePrimaryKey($entity);

            $this->dynamoDbClient->deleteItem(
                [
                    'TableName' => $table,
                    'Key' => $key,
                ]
            );
        } catch (Throwable $exception) {
            $this->wrapException($exception);
        }
    }

    /**
     * @param class-string $class
     * @throws EntityManagerException
     */
    public function describe(string $class): ResultStream
    {
        $result = (function () use ($class): Generator {
            try {
                $table = $this->metadataLoader->getEntityMetadata($class)->getTable();
                yield from $this->dynamoDbClient->describeTable(['TableName' => $table]);
            } catch (Throwable $exception) {
                $this->wrapException($exception);
            }
        })();

        return new ResultStream($result);
    }

    /**
     * @throws EntityManagerException
     */
    private function wrapException(Throwable $exception): never
    {
        throw new EntityManagerException(
            sprintf('An error occurred. %s: %s', $exception::class, $exception->getMessage())
        );
    }
}
