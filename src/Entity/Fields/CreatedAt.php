<?php

declare(strict_types=1);

namespace App\Entity\Fields;

use Doctrine\ORM\Mapping as ORM;

trait CreatedAt
{
    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function initCreatedAt(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
