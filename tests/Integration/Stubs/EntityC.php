<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Tests\Integration\Stubs;

use DateTimeInterface;
use EduardoMarques\DynamoPHP\Attribute\Attribute;
use EduardoMarques\DynamoPHP\Attribute\Entity;
use EduardoMarques\DynamoPHP\Attribute\GlobalIndex;
use EduardoMarques\DynamoPHP\Attribute\LocalIndex;
use EduardoMarques\DynamoPHP\Attribute\PartitionKey;
use EduardoMarques\DynamoPHP\Attribute\SortKey;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[Entity(
    table: 'test-table-index',
    partitionKey: new PartitionKey(fields: ['id']),
    sortKey: new SortKey(fields: ['creationDate']),
    indexes: [
        new GlobalIndex(
            name: 'GSI1',
            partitionKey: new PartitionKey(fields: ['name'], name: 'GSI1_PK'),
            sortKey: new SortKey(fields: ['id'], name: 'GSI1_SK')
        ),
        new LocalIndex(name: 'LSI1', sortKey: new SortKey(fields: ['name'], name: 'LSI1_SK')),
    ]
)]
final class EntityC
{
    #[Attribute]
    #[SerializedName('fullName')]
    public string $name;

    #[Attribute(name: 'type')]
    public EnumA $enumA = EnumA::TYPE_A;

    public EntityB $b;

    #[Attribute(name: 'createdAt')]
    public DateTimeInterface $creationDate;

    public function __construct(
        #[Attribute]
        protected string $id,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }
}
