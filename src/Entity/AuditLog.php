<?php

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_logs')]
#[ORM\Index(columns: ['created_at'])]
#[ORM\Index(columns: ['entity', 'entity_id'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'integer', options: ['unsigned' => true], nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(name: 'user_name', length: 150, nullable: true)]
    private ?string $userName = null;

    #[ORM\Column(name: 'centre_id', type: 'integer', options: ['unsigned' => true], nullable: true)]
    private ?int $centreId = null;

    #[ORM\Column(length: 30)]
    private string $action = '';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $entity = null;

    #[ORM\Column(name: 'entity_id', type: 'integer', options: ['unsigned' => true], nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $details = null;

    #[ORM\Column(name: 'ip_address', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'prev_hash', type: 'string', length: 64, nullable: true, options: ['fixed' => true])]
    private ?string $prevHash = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true, options: ['fixed' => true])]
    private ?string $hash = null;

    public static function create(
        ?int $userId,
        ?string $userName,
        ?int $centreId,
        string $action,
        ?string $entity,
        ?int $entityId,
        mixed $details,
        ?string $ip,
        ?string $prevHash
    ): self {
        $log = new self();
        $log->userId = $userId;
        $log->userName = $userName;
        $log->centreId = $centreId;
        $log->action = $action;
        $log->entity = $entity;
        $log->entityId = $entityId;
        $log->details = is_string($details) ? $details : json_encode($details);
        $log->ipAddress = $ip;
        $log->createdAt = new \DateTimeImmutable();
        $log->prevHash = $prevHash;
        $log->hash = hash('sha256', implode('|', [
            $log->userId, $log->action, $log->entity, $log->entityId,
            $log->details, $log->createdAt->format('Y-m-d H:i:s'), $prevHash,
        ]));
        return $log;
    }

    public function getId(): ?int { return $this->id; }
    public function getUserId(): ?int { return $this->userId; }
    public function getUserName(): ?string { return $this->userName; }
    public function getCentreId(): ?int { return $this->centreId; }
    public function getAction(): string { return $this->action; }
    public function getEntity(): ?string { return $this->entity; }
    public function getEntityId(): ?int { return $this->entityId; }
    public function getDetails(): mixed { return $this->details ? json_decode($this->details, true) ?? $this->details : null; }
    public function getDetailsRaw(): ?string { return $this->details; }
    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getPrevHash(): ?string { return $this->prevHash; }
    public function getHash(): ?string { return $this->hash; }
}
