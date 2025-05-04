<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\ODM;

use Aws\DynamoDb\DynamoDbClient;
use EduardoMarques\DynamoPHP\Metadata\MetadataLoader;
use EduardoMarques\DynamoPHP\Serializer\EntityDenormalizer;
use EduardoMarques\DynamoPHP\Serializer\EntityNormalizer;
use EduardoMarques\DynamoPHP\Serializer\EntitySerializer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class EntityManagerFactory
{
    /**
     * @param array<string, mixed> $dbClientOptions
     * @param array<string, mixed> $options
     */
    public static function create(array $dbClientOptions, array $options = []): EntityManager
    {
        $datetimeFormat = $options[EntityNormalizer::DATETIME_FORMAT_KEY] ?? 'Y-m-d\TH:i:s.v\Z';

        $dynamoDbClient = new DynamoDbClient($dbClientOptions);
        $metadataLoader = new MetadataLoader();
        $serializer = new Serializer([
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => $datetimeFormat]),
            new BackedEnumNormalizer(),
            new ObjectNormalizer(),
        ]);

        $normalizer = new EntityNormalizer($metadataLoader, $serializer);
        $denormalizer = new EntityDenormalizer($metadataLoader, $serializer);
        $entitySerializer = new EntitySerializer($normalizer, $denormalizer);

        return new EntityManager($dynamoDbClient, $metadataLoader, $entitySerializer);
    }
}
