<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'login_history')]
#[ORM\Index(columns: ['user_id', 'created_at'])]
#[ORM\Index(columns: ['created_at'])]
class LoginHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'integer', options: ['unsigned' => true], nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(length: 191, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(type: 'smallint')]
    private int $success = 0;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(name: 'ip_address', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(name: 'user_agent', length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $browser = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $platform = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public static function record(?int $userId, ?string $username, bool $success, ?string $reason, ?string $ip, ?string $ua): self
    {
        $h = new self();
        $h->userId = $userId;
        $h->username = $username;
        $h->success = $success ? 1 : 0;
        $h->reason = $reason;
        $h->ipAddress = $ip;
        $h->userAgent = $ua;
        $h->createdAt = new \DateTimeImmutable();
        return $h;
    }

    public function getId(): ?int { return $this->id; }
    public function getUserId(): ?int { return $this->userId; }
    public function getUsername(): ?string { return $this->username; }
    public function isSuccess(): bool { return $this->success === 1; }
    public function getReason(): ?string { return $this->reason; }
    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function getBrowser(): ?string { return $this->browser; }
    public function getPlatform(): ?string { return $this->platform; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
}
