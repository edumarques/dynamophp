<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Tests\Unit\Stubs;

use DateTimeInterface;
use EduardoMarques\DynamoPHP\Attribute\Attribute;
use EduardoMarques\DynamoPHP\Attribute\Entity;
use EduardoMarques\DynamoPHP\Attribute\PartitionKey;
use EduardoMarques\DynamoPHP\Attribute\SortKey;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[Entity(
    table: 'tests',
    partitionKey: new PartitionKey(['id']),
    sortKey: new SortKey(['creationDate'])
)]
final class EntityA
{
    #[Attribute]
    public int $id;

    public string $firstName;

    public string $lastName;

    #[Attribute]
    #[SerializedName('fullName')]
    public string $name;

    #[Attribute(name: 'createdAt')]
    public DateTimeInterface $creationDate;

    public function __construct(
        #[Attribute]
        protected string $cardNumber
    ) {
    }

    public function getCardNumber(): string
    {
        return $this->cardNumber;
    }
}
