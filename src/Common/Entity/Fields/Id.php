<?php

declare(strict_types=1);

namespace App\Common\Entity\Fields;

use Doctrine\ORM\Mapping as ORM;

trait Id
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
