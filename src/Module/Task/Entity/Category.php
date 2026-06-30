<?php

declare(strict_types=1);

namespace App\Module\Task\Entity;

use App\Common\Entity\Fields\CreatedAt;
use App\Common\Entity\Fields\Id;
use App\Common\Entity\Fields\UpdatedAt;
use App\Module\Main\Entity\User;
use App\Module\Task\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'categories')]
#[ORM\UniqueConstraint(name: 'uniq_category_user_name', columns: ['user_id', 'name'])]
#[ORM\HasLifecycleCallbacks]
class Category
{
    use Id;
    use CreatedAt;
    use UpdatedAt;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $color = '#3498db';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icon = null;

    /** @var Collection<int, Task> */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'category')]
    private Collection $tasks;

    public function __construct(User $user)
    {
        $this->initCreatedAt();
        $this->tasks = new ArrayCollection();
        $this->color = '#3498db';
        $this->user = $user;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /** @return Collection<int, Task> */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function getTaskCount(): int
    {
        return $this->tasks->count();
    }
}
