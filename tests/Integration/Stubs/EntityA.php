<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Tests\Integration\Stubs;

use DateTimeInterface;
use EduardoMarques\DynamoPHP\Attribute\Attribute;
use EduardoMarques\DynamoPHP\Attribute\Entity;
use EduardoMarques\DynamoPHP\Attribute\PartitionKey;
use EduardoMarques\DynamoPHP\Attribute\SortKey;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[Entity(
    table: 'test-table',
    partitionKey: new PartitionKey(['id']),
    sortKey: new SortKey(['creationDate'])
)]
final class EntityA
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
