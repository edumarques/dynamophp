<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\ODM;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use EduardoMarques\DynamoPHP\Metadata\MetadataLoader;
use EduardoMarques\DynamoPHP\Serializer\EntitySerializer;

class EntityManager
{
    public function __construct(
        protected DynamoDbClient $dynamoDbClient,
        protected Marshaler $dynamoDbMarshaler,
        protected MetadataLoader $metadataLoader,
        protected EntitySerializer $entitySerializer,
    ) {
    }

    /**
     * @param class-string $class
     * @param array<string, mixed> $keyFieldValues
     *
     * @throws EntityManagerException
     */
    public function find(string $class, array $keyFieldValues): ?object
    {
        try {
            $key = $this->entitySerializer->normalizePrimaryKey($class, $keyFieldValues);

            if (2 > count($key)) {
                throw new EntityManagerException('Fields of both Partition and Sort keys must be provided');
            }

            $rawKey = $this->dynamoDbMarshaler->marshalItem($key);
            $table = $this->metadataLoader->getEntityMetadata($class)->getTable();

            $result = $this->dynamoDbClient->getItem(
                [
                    'TableName' => $table,
                    'Key' => $rawKey,
                ]
            );

            $rawItem = $result['Item'] ?? null;

            if (null === $rawItem) {
                return null;
            }

            $item = $this->dynamoDbMarshaler->unmarshalItem($rawItem);

            return $this->entitySerializer->denormalize($item, $class);
        } catch (\Throwable $exception) {
            $this->wrapException($exception);
        }
    }

    /**
     * @throws EntityManagerException
     */
    public function save(object $entity): void
    {
        try {
            $item = $this->entitySerializer->normalize($entity);
            $rawItem = $this->dynamoDbMarshaler->marshalItem($item);
            $table = $this->metadataLoader->getEntityMetadata($entity::class)->getTable();

            $this->dynamoDbClient->putItem(
                [
                    'TableName' => $table,
                    'Item' => $rawItem,
                ]
            );
        } catch (\Throwable $exception) {
            $this->wrapException($exception);
        }
    }

    /**
     * @throws EntityManagerException
     */
    public function remove(object $entity): void
    {
        try {
            $key = $this->entitySerializer->normalizePrimaryKey($entity);
            $rawKey = $this->dynamoDbMarshaler->marshalItem($key);
            $table = $this->metadataLoader->getEntityMetadata($entity::class)->getTable();

            $this->dynamoDbClient->deleteItem(
                [
                    'TableName' => $table,
                    'Key' => $rawKey,
                ]
            );
        } catch (\Throwable $exception) {
            $this->wrapException($exception);
        }
    }

    /**
     * @throws EntityManagerException
     */
    private function wrapException(\Throwable $exception): void
    {
        throw new EntityManagerException($exception->getMessage());
    }
}
