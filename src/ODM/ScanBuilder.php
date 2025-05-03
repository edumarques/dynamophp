<?php

declare(strict_types=1);

namespace EduardoMarques\DynamoPHP\ODM;

class ScanBuilder extends AbstractBuilder
{
    public function segment(int $segment): self
    {
        $this->parameters['Segment'] = $segment;

        return $this;
    }

    public function totalSegments(int $totalSegments): self
    {
        $this->parameters['TotalSegments'] = $totalSegments;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function build(): array
    {
        return $this->parameters;
    }
}
