<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLES = [
        'sysadmin'  => 'System Administrator',
        'admin'     => 'HQ Administrator',
        'executive' => 'Executive / Overseer',
        'centre_admin' => 'Centre Administrator',
        'clerk'     => 'Data Clerk',
        'volunteer' => 'Volunteer',
        'viewer'    => 'Viewer (Read-only)',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Centre::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Centre $centre = null;

    #[ORM\Column(length: 150)]
    private string $name = '';

    #[ORM\Column(length: 60, unique: true)]
    private string $username = '';

    #[ORM\Column(length: 191, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(name: 'password_hash', length: 255)]
    private string $passwordHash = '';

    #[ORM\Column(length: 20)]
    private string $role = 'clerk';

    #[ORM\Column(name: 'is_active', type: 'smallint')]
    private int $isActive = 1;

    #[ORM\Column(name: 'failed_attempts', type: 'smallint', options: ['unsigned' => true])]
    private int $failedAttempts = 0;

    #[ORM\Column(name: 'locked_until', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeInterface $lockedUntil = null;

    #[ORM\Column(name: 'last_login_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(name: 'last_login_ip', length: 45, nullable: true)]
    private ?string $lastLoginIp = null;

    #[ORM\Column(name: 'totp_secret', type: 'text', nullable: true)]
    private ?string $totpSecret = null;

    #[ORM\Column(name: 'totp_recovery', type: 'text', nullable: true)]
    private ?string $totpRecovery = null;

    #[ORM\Column(name: 'reset_hash', length: 64, nullable: true)]
    private ?string $resetHash = null;

    #[ORM\Column(name: 'reset_expires', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeInterface $resetExpires = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $locale = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $prefs = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // UserInterface
    public function getUserIdentifier(): string { return $this->username; }
    public function getRoles(): array { return ['ROLE_USER', 'ROLE_' . strtoupper($this->role)]; }
    public function eraseCredentials(): void {}
    public function getPassword(): string { return $this->passwordHash; }

    public function getId(): ?int { return $this->id; }
    public function getCentre(): ?Centre { return $this->centre; }
    public function setCentre(?Centre $c): self { $this->centre = $c; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $v): self { $this->name = $v; return $this; }
    public function getUsername(): string { return $this->username; }
    public function setUsername(string $v): self { $this->username = $v; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $v): self { $this->email = $v; return $this; }
    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $v): self { $this->phone = $v; return $this; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function setPasswordHash(string $v): self { $this->passwordHash = $v; return $this; }
    public function getRole(): string { return $this->role; }
    public function setRole(string $v): self { $this->role = $v; return $this; }
    public function isActive(): bool { return $this->isActive === 1; }
    public function setIsActive(bool $v): self { $this->isActive = $v ? 1 : 0; return $this; }
    public function getFailedAttempts(): int { return $this->failedAttempts; }
    public function setFailedAttempts(int $v): self { $this->failedAttempts = $v; return $this; }
    public function getLockedUntil(): ?\DateTimeInterface { return $this->lockedUntil; }
    public function setLockedUntil(?\DateTimeInterface $v): self { $this->lockedUntil = $v; return $this; }
    public function getLastLoginAt(): ?\DateTimeInterface { return $this->lastLoginAt; }
    public function setLastLoginAt(?\DateTimeInterface $v): self { $this->lastLoginAt = $v; return $this; }
    public function getLastLoginIp(): ?string { return $this->lastLoginIp; }
    public function setLastLoginIp(?string $v): self { $this->lastLoginIp = $v; return $this; }
    public function getTotpSecret(): ?string { return $this->totpSecret; }
    public function setTotpSecret(?string $v): self { $this->totpSecret = $v; return $this; }
    public function getResetHash(): ?string { return $this->resetHash; }
    public function setResetHash(?string $v): self { $this->resetHash = $v; return $this; }
    public function getResetExpires(): ?\DateTimeInterface { return $this->resetExpires; }
    public function setResetExpires(?\DateTimeInterface $v): self { $this->resetExpires = $v; return $this; }
    public function getLocale(): ?string { return $this->locale; }
    public function setLocale(?string $v): self { $this->locale = $v; return $this; }
    public function getPrefs(): array { return $this->prefs ? (json_decode($this->prefs, true) ?? []) : []; }
    public function setPrefs(array $v): self { $this->prefs = json_encode($v) ?: null; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function getRoleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }

    public function isSysAdmin(): bool { return $this->role === 'sysadmin'; }
    public function isAdmin(): bool { return in_array($this->role, ['sysadmin', 'admin'], true); }
    public function isLocked(): bool
    {
        return $this->lockedUntil !== null && $this->lockedUntil > new \DateTimeImmutable();
    }
}
