<?php

declare(strict_types=1);

namespace App\Module\Task\Entity;

use App\Common\Entity\Fields\CreatedAt;
use App\Common\Entity\Fields\Id;
use App\Common\Entity\Fields\UpdatedAt;
use App\Module\Task\Entity\Category;
use App\Module\Main\Entity\User;
use App\Module\Task\Enum\TaskPriority;
use App\Module\Task\Enum\TaskStatus;
use App\Module\Task\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'tasks')]
#[ORM\Index(name: 'idx_status', columns: ['status'])]
#[ORM\Index(name: 'idx_priority', columns: ['priority'])]
#[ORM\Index(name: 'idx_created_at', columns: ['created_at'])]
#[ORM\HasLifecycleCallbacks]
class Task
{
    use Id;
    use CreatedAt;
    use UpdatedAt;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(type: 'string', enumType: TaskStatus::class)]
    private TaskStatus $status;

    #[ORM\Column(type: 'string', enumType: TaskPriority::class)]
    private TaskPriority $priority;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Task $parent = null;

    /** @var Collection<int, TaskComment> */
    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TaskComment::class, orphanRemoval: true)]
    private Collection $comments;


    /** @var Collection<int, Task> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ['persist', 'remove'])]
    private Collection $children;

    public static function create(
        User $user,
        string $title,
        TaskPriority $priority = TaskPriority::Medium,
        ?Category $category = null,
    ): self {
        $task = new self();
        $task->user = $user;
        $task->title = $title;
        $task->priority = $priority;
        $task->category = $category;
        $task->status = TaskStatus::Waiting;
        $task->children = new ArrayCollection();
        $task->initCreatedAt();

        return $task;
    }

    public static function createSubtask(
        Task $parent,
        User $user,
        string $title,
        TaskPriority $priority = TaskPriority::Medium,
    ): self {
        $subtask = self::create($user, $title, $priority);
        $parent->addChild($subtask);

        return $subtask;
    }

    private function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function rename(string $title): void
    {
        $this->title = $title;
    }

    public function describe(?string $description): void
    {
        $this->description = $description;
    }

    public function schedule(
        ?\DateTimeInterface $startTime,
        ?\DateTimeInterface $endTime,
    ): void {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    public function changePriority(TaskPriority $priority): void
    {
        $this->priority = $priority;
    }

    public function assignCategory(?Category $category): void
    {
        $this->category = $category;
    }

    public function changeStatus(TaskStatus $status): void
    {
        $this->status = $status;
    }

    public function start(): void
    {
        $this->changeStatus(TaskStatus::InProgress);
    }

    public function complete(): void
    {
        $this->changeStatus(TaskStatus::Completed);
    }

    public function reopen(): void
    {
        $this->changeStatus(TaskStatus::Waiting);
    }

    public function addChild(Task $child): void
    {
        if ($child === $this) {
            throw new \LogicException('Задача не может быть подзадачей самой себя.');
        }

        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->parent = $this;
        }
    }

    public function removeChild(Task $child): void
    {
        if ($this->children->removeElement($child)) {
            if ($child->parent === $this) {
                $child->parent = null;
            }
        }
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function getPriority(): TaskPriority
    {
        return $this->priority;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function getParent(): ?Task
    {
        return $this->parent;
    }

    /** @return Collection<int, Task> */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function isSubtask(): bool
    {
        return null !== $this->parent;
    }

    public function hasChildren(): bool
    {
        return !$this->children->isEmpty();
    }

    public function isCompleted(): bool
    {
        return TaskStatus::Completed === $this->status;
    }

    public function isOverdue(): bool
    {
        return null !== $this->endTime
            && $this->endTime < new \DateTimeImmutable()
            && !$this->isCompleted();
    }

    /** @return Collection<int, TaskComment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(TaskComment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setTask($this);
        }
        return $this;
    }
    public function removeComment(TaskComment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getTask() === $this) {
                $comment->setTask(null);
            }
        }
        return $this;
    }
}
