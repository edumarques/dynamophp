<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Tests\Integration\ODM;

use DateTime;
use EduardoMarques\DynamoPHP\ODM\EntityManager;
use EduardoMarques\DynamoPHP\ODM\EntityManagerException;
use EduardoMarques\DynamoPHP\ODM\EntityManagerFactory;
use EduardoMarques\DynamoPHP\ODM\QueryArgs;
use EduardoMarques\DynamoPHP\ODM\ScanArgs;
use EduardoMarques\DynamoPHP\Tests\Integration\Stubs\EntityA;
use EduardoMarques\DynamoPHP\Tests\Integration\Stubs\EntityB;
use EduardoMarques\DynamoPHP\Tests\Integration\Stubs\EntityC;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EntityManagerTest extends TestCase
{
    private static EntityManager $entityManager;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$entityManager = EntityManagerFactory::create([
            'region' => 'eu-central-1',
            'endpoint' => 'http://localstack:4566',
            'credentials' => ['key' => 'key', 'secret' => 'secret'],
        ]);
    }

    #[Test]
    public function itGetsEntity(): void
    {
        $id = '8798b91f-fe8e-498c-8145-c757029346ef';
        $creationDate = new DateTime();
        $entity = new EntityA($id);
        $entity->name = 'John Doe';
        $entity->creationDate = $creationDate;

        self::$entityManager->put($entity);

        $persistedEntity = self::$entityManager->get(EntityA::class, ['id' => $id, 'creationDate' => $creationDate]);

        $this->assertEquals($entity, $persistedEntity);

        self::$entityManager->delete($entity);
    }

    #[Test]
    public function itReturnsNullWhenEntityIsNotFoundWhileGetting(): void
    {
        $id = '8798b91f-fe8e-498c-8145-c757029346ef';
        $creationDate = new DateTime();

        $persistedEntity = self::$entityManager->get(EntityA::class, ['id' => $id, 'creationDate' => $creationDate]);

        $this->assertNull($persistedEntity);
    }

    #[Test]
    public function itWrapsExceptionThrownWhileTryingToGetEntity(): void
    {
        $id = '8798b91f-fe8e-498c-8145-c757029346ef';

        $this->expectException(EntityManagerException::class);
        $this->expectExceptionMessageMatches('/^An error occurred\. .*Exception:.+/');

        self::$entityManager->get(EntityA::class, ['id' => $id]);
    }

    #[Test]
    public function itQueriesEntities(): void
    {
        $id1 = '8798b91f-fe8e-498c-8145-c757029346ef';
        $creationDate1 = new DateTime('2025-01-02');
        $entity1 = new EntityA($id1);
        $entity1->name = 'John Doe';
        $entity1->creationDate = $creationDate1;

        $id2 = '49fbcfb9-4fe7-4204-8001-29b826d903cb';
        $creationDate2 = new DateTime('2025-01-03');
        $entity2 = new EntityA($id2);
        $entity2->name = 'Mary Jane';
        $entity2->creationDate = $creationDate2;

        $id3 = $id1;
        $creationDate3 = new DateTime('2025-01-04');
        $entity3 = new EntityA($id3);
        $entity3->name = 'John Doe Jr.';
        $entity3->creationDate = $creationDate3;

        self::$entityManager->put($entity1);
        self::$entityManager->put($entity2);
        self::$entityManager->put($entity3);

        $queryArgs = (new QueryArgs())
            ->keyConditionExpression('PK = :pk AND SK BETWEEN :start AND :end')
            ->expressionAttributeValues([
                ':pk' => $id1,
                ':start' => new DateTime('2025-01-01'),
                ':end' => new DateTime('2025-01-10'),
            ]);

        $result = self::$entityManager->query(EntityA::class, $queryArgs);

        $this->assertEquals([$entity1, $entity3], $result->getResult(true));

        self::$entityManager->delete($entity1);
        self::$entityManager->delete($entity2);
        self::$entityManager->delete($entity3);
    }

    #[Test]
    public function itQueriesEntitiesWithLimit(): void
    {
        $id1 = '8798b91f-fe8e-498c-8145-c757029346ef';
        $creationDate1 = new DateTime('2025-01-02');
        $entity1 = new EntityA($id1);
        $entity1->name = 'John Doe';
        $entity1->creationDate = $creationDate1;

        $id2 = '49fbcfb9-4fe7-4204-8001-29b826d903cb';
        $creationDate2 = new DateTime('2025-01-03');
        $entity2 = new EntityA($id2);
        $entity2->name = 'Mary Jane';
        $entity2->creationDate = $creationDate2;

        $id3 = $id1;
        $creationDate3 = new DateTime('2025-01-04');
        $entity3 = new EntityA($id3);
        $entity3->name = 'John Doe Jr.';
        $entity3->creationDate = $creationDate3;

        self::$entityManager->put($entity1);
        self::$entityManager->put($entity2);
        self::$entityManager->put($entity3);

        $queryArgs = (new QueryArgs())
            ->limit(1)
            ->keyConditionExpression('PK = :pk AND SK BETWEEN :start AND :end')
            ->expressionAttributeValues([
                ':pk' => $id1,
                ':start' => new DateTime('2025-01-01'),
                ':end' => new DateTime('2025-01-10'),
            ]);

        $result = self::$entityManager->query(EntityA::class, $queryArgs);

        $this->assertEquals([$entity1], $result->getResult(true));

        self::$entityManager->delete($entity1);
        self::$entityManager->delete($entity2);
        self::$entityManager->delete($entity3);
    }

    #[Test]
    public function itQueriesEntitiesOnLocalIndex(): void
    {
        $id1 = '8798b91f-fe8e-498c-8145-c757029346ef';
        $creationDate1 = new DateTime('2025-01-02');
        $entity1 = new EntityC($id1);
        $entity1->name = 'John Doe';
        $entity1->creationDate = $creationDate1;

        self::$entityManager->put($entity1);

        $queryArgs = (new QueryArgs())
            ->indexName('LSI1')
            ->keyConditionExpression('PK = :pk AND begins_with(LSI1_SK, :sk)')
            ->expressionAttributeValues([
                ':pk' => $id1,
                ':sk' => 'J',
            ]);

        $result = self::$entityManager->query(EntityC::class, $queryArgs);

        $this->assertEquals([$entity1], $result->getResult(true));

        self::$entityManager->delete($entity1);
    }

    #[Test]
    public function itQueriesEntitiesOnGlobalIndex(): void
    {
        $id1 = '8798b91f-fe8e-498c-8145-c757029346ef';
        $creationDate1 = new DateTime('2025-01-02');
        $entity1 = new EntityC($id1);
        $entity1->name = 'John Doe';
        $entity1->creationDate = $creationDate1;

        self::$entityManager->put($entity1);

        $queryArgs = (new QueryArgs())
            ->indexName('GSI1')
            ->keyConditionExpression('GSI1_PK = :pk AND begins_with(GSI1_SK, :sk)')
            ->expressionAttributeValues([
                ':pk' => 'John Doe',
                ':sk' => '879',
            ]);

        $result = self::$entityManager->query(EntityC::class, $queryArgs);

        $this->assertEquals([$entity1], $result->getResult(true));

        self::$entityManager->delete($entity1);
    }

    #[Test]
    public function itQueriesOneEntity(): void
    {
        $id1 = '8798b91f-fe8e-498c-8145-c757029346ef';
        $creationDate1 = new DateTime('2025-01-02');
        $entity1 = new EntityA($id1);
        $entity1->name = 'John Doe';
        $entity1->creationDate = $creationDate1;

        $id2 = '49fbcfb9-4fe7-4204-8001-29b826d903cb';
        $creationDate2 = new DateTime('2025-01-03');
        $entity2 = new EntityA($id2);
        $entity2->name = 'Mary Jane';
        $entity2->creationDate = $creationDate2;

        self::$entityManager->put($entity1);
        self::$entityManager->put($entity2);

        $queryArgs = (new QueryArgs())
            ->limit(1)
            ->keyConditionExpression('PK = :pk AND SK BETWEEN :start AND :end')
            ->expressionAttributeValues([
                ':pk' => $id1,
                ':start' => new DateTime('2025-01-01'),
                ':end' => new DateTime('2025-01-10'),
            ]);

        $result = self::$entityManager->queryOne(EntityA::class, $queryArgs);

        $this->assertEquals($entity1, $result);

        self::$entityManager->delete($entity1);
        self::$entityManager->delete($entity2);
    }

    #[Test]
    public function itReturnsNullWhenEntityIsNotFoundWhileQuerying(): void
    {
        $id = '8798b91f-fe8e-498c-8145-c757029346ef';

        $queryArgs = (new QueryArgs())
            ->limit(1)
            ->keyConditionExpression('PK = :pk AND SK BETWEEN :start AND :end')
            ->expressionAttributeValues([
                ':pk' => $id,
                ':start' => new DateTime('2025-01-01'),
                ':end' => new DateTime('2025-01-10'),
            ]);

        $result = self::$entityManager->queryOne(EntityA::class, $queryArgs);

        $this->assertNull($result);
    }

    #[Test]
    public function itWrapsExceptionThrownWhileTryingToQueryEntities(): void
    {
        $this->expectException(EntityManagerException::class);
        $this->expectExceptionMessageMatches('/^An error occurred\. .*Exception:.+/');

        $queryArgs = new QueryArgs();

        self::$entityManager->query(EntityA::class, $queryArgs)->getResult(true);
    }

    #[Test]
    public function itScansAllEntities(): void
    {
        $id1 = '8798b91f-fe8e-498c-8145-c757029346ef';
        $creationDate1 = new DateTime('2025-01-02');
        $entity1 = new EntityA($id1);
        $entity1->name = 'John Doe';
        $entity1->creationDate = $creationDate1;

        $id2 = '49fbcfb9-4fe7-4204-8001-29b826d903cb';
        $creationDate2 = new DateTime('2025-01-03');
        $entity2 = new EntityA($id2);
        $entity2->name = 'Mary Jane';
        $entity2->creationDate = $creationDate2;

        $id3 = $id1;
        $creationDate3 = new DateTime('2025-01-04');
        $entity3 = new EntityA($id3);
        $entity3->name = 'John Doe Jr.';
        $entity3->creationDate = $creationDate3;

        self::$entityManager->put($entity1);
        self::$entityManager->put($entity2);
        self::$entityManager->put($entity3);

        $scanArgs = new ScanArgs();

        $result = self::$entityManager->scan(EntityA::class, $scanArgs);

        $this->assertEquals([$entity2, $entity1, $entity3], $result->getResult(true));

        self::$entityManager->delete($entity1);
        self::$entityManager->delete($entity2);
        self::$entityManager->delete($entity3);
    }

    #[Test]
    public function itScansEntities(): void
    {
        $id1 = '8798b91f-fe8e-498c-8145-c757029346ef';
        $creationDate1 = new DateTime('2025-01-02');
        $entity1 = new EntityA($id1);
        $entity1->name = 'John Doe';
        $entity1->creationDate = $creationDate1;

        $id2 = '49fbcfb9-4fe7-4204-8001-29b826d903cb';
        $creationDate2 = new DateTime('2025-01-03');
        $entity2 = new EntityA($id2);
        $entity2->name = 'Mary Jane';
        $entity2->creationDate = $creationDate2;

        $id3 = $id1;
        $creationDate3 = new DateTime('2025-01-04');
        $entity3 = new EntityA($id3);
        $entity3->name = 'John Doe Jr.';
        $entity3->creationDate = $creationDate3;

        self::$entityManager->put($entity1);
        self::$entityManager->put($entity2);
        self::$entityManager->put($entity3);

        $scanArgs = (new ScanArgs())
            ->filterExpression('PK = :pk AND SK BETWEEN :start AND :end')
            ->expressionAttributeValues([
                ':pk' => $id1,
                ':start' => new DateTime('2025-01-01'),
                ':end' => new DateTime('2025-01-10'),
            ]);

        $result = self::$entityManager->scan(EntityA::class, $scanArgs);

        $this->assertEquals([$entity1, $entity3], $result->getResult(true));

        self::$entityManager->delete($entity1);
        self::$entityManager->delete($entity2);
        self::$entityManager->delete($entity3);
    }

    #[Test]
    public function itScansEntitiesWithLimit(): void
    {
        $id1 = '8798b91f-fe8e-498c-8145-c757029346ef';
        $creationDate1 = new DateTime('2025-01-02');
        $entity1 = new EntityA($id1);
        $entity1->name = 'John Doe';
        $entity1->creationDate = $creationDate1;

        $id2 = '49fbcfb9-4fe7-4204-8001-29b826d903cb';
        $creationDate2 = new DateTime('2025-01-03');
        $entity2 = new EntityA($id2);
        $entity2->name = 'Mary Jane';
        $entity2->creationDate = $creationDate2;

        self::$entityManager->put($entity1);
        self::$entityManager->put($entity2);

        $scanArgs = (new ScanArgs())
            ->limit(1)
            ->filterExpression('PK = :pk AND SK BETWEEN :start AND :end')
            ->expressionAttributeValues([
                ':pk' => $id1,
                ':start' => new DateTime('2025-01-01'),
                ':end' => new DateTime('2025-01-10'),
            ]);

        $result = self::$entityManager->scan(EntityA::class, $scanArgs);

        $this->assertEquals([$entity1], $result->getResult(true));

        self::$entityManager->delete($entity1);
        self::$entityManager->delete($entity2);
    }

    #[Test]
    public function itWrapsExceptionThrownWhileTryingToScanEntities(): void
    {
        $this->expectException(EntityManagerException::class);
        $this->expectExceptionMessageMatches('/^An error occurred\. .*Exception:.+/');

        $scanArgs = (new ScanArgs())->filterExpression('invalid expression');

        self::$entityManager->scan(EntityA::class, $scanArgs)->getResult(true);
    }

    #[Test]
    public function itWrapsExceptionThrownWhileTryingToPutEntity(): void
    {
        $this->expectException(EntityManagerException::class);
        $this->expectExceptionMessageMatches('/^An error occurred\. .*Exception:.+/');

        $entity = new EntityB();
        $entity->id = 1;

        self::$entityManager->put($entity);
    }

    #[Test]
    public function itWrapsExceptionThrownWhileTryingToDeleteEntity(): void
    {
        $this->expectException(EntityManagerException::class);
        $this->expectExceptionMessageMatches('/^An error occurred\. .*Exception:.+/');

        $entity = new EntityB();
        $entity->id = 1;

        self::$entityManager->delete($entity);
    }

    #[Test]
    public function itDescribesEntity(): void
    {
        /** @var array<string, mixed> $description */
        $description = self::$entityManager->describe(EntityA::class)->getResult(true);

        $this->assertArrayHasKey('Table', $description);
        $this->assertArrayHasKey('@metadata', $description);
    }

    #[Test]
    public function itWrapsExceptionThrownWhileTryingToDescribeEntity(): void
    {
        $this->expectException(EntityManagerException::class);
        $this->expectExceptionMessageMatches('/^An error occurred\. .*Exception:.+/');

        self::$entityManager->describe(EntityB::class)->getResult(true);
    }
}
