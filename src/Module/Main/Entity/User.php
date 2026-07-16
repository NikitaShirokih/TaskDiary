<?php

declare(strict_types=1);

namespace App\Module\Main\Entity;

use App\Module\Main\Enum\UserRole;
use App\Module\Main\Repository\UserRepository;
use App\Module\Task\Entity\Task;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /** @var array<int, string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $emailVerificationTokenExpiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $passwordResetTokenHash = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $passwordResetTokenExpiresAt = null;

    /** @var Collection<int, Task> */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $tasks;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    protected function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /** @return array<int, string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = UserRole::USER->value;

        return array_values(array_unique($roles));
    }

    /** @param array<int, string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    public function addRole(UserRole $role): static
    {
        if (!in_array($role->value, $this->roles, true)) {
            $this->roles[] = $role->value;
        }

        return $this;
    }

    public function removeRole(UserRole $role): static
    {
        $this->roles = array_values(array_filter(
            $this->roles,
            static fn(string $existingRole): bool => $existingRole !== $role->value
        ));

        return $this;
    }

    public function hasRole(UserRole $role): bool
    {
        return in_array($role->value, $this->getRoles(), true);
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function requestEmailVerification(string $tokenHash, \DateTimeImmutable $expiresAt): void
    {
        $this->emailVerificationToken = $tokenHash;
        $this->emailVerificationTokenExpiresAt = $expiresAt;
    }

    public function verifyEmail(\DateTimeImmutable $verifiedAt): void
    {
        $this->isVerified = true;
        $this->emailVerifiedAt = $verifiedAt;
        $this->emailVerificationToken = null;
        $this->emailVerificationTokenExpiresAt = null;
    }

    public function getEmailVerificationTokenHash(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function getEmailVerificationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->emailVerificationTokenExpiresAt;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function isEmailVerificationTokenExpired(\DateTimeImmutable $now): bool
    {
        return $this->emailVerificationTokenExpiresAt !== null
            && $this->emailVerificationTokenExpiresAt <= $now;
    }

    public function requestPasswordReset(string $tokenHash, \DateTimeImmutable $expiresAt): void
    {
        $this->passwordResetTokenHash = $tokenHash;
        $this->passwordResetTokenExpiresAt = $expiresAt;
    }

    public function clearPasswordResetToken(): void
    {
        $this->passwordResetTokenHash = null;
        $this->passwordResetTokenExpiresAt = null;
    }

    public function getPasswordResetTokenHash(): ?string
    {
        return $this->passwordResetTokenHash;
    }

    public function getPasswordResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetTokenExpiresAt;
    }

    public function isPasswordResetTokenExpired(\DateTimeImmutable $now): bool
    {
        return $this->passwordResetTokenExpiresAt !== null
            && $this->passwordResetTokenExpiresAt <= $now;
    }

    /** @return Collection<int, Task> */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        $this->tasks->removeElement($task);

        return $this;
    }
}
