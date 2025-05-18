<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\Tests\Unit\Stubs;

use DateTimeInterface;
use EduardoMarques\DynamoPHP\Attribute\Attribute;

class EntityB extends EntityA
{
    #[Attribute('fullName')]
    public string $name;

    #[Attribute]
    public DateTimeInterface $creationDate;

    #[Attribute]
    public string $type;
}
