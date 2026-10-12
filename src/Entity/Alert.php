<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'alerts')]
#[ORM\Index(columns: ['is_read', 'created_at'])]
class Alert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'centre_id', type: 'integer', options: ['unsigned' => true], nullable: true)]
    private ?int $centreId = null;

    #[ORM\Column(name: 'user_id', type: 'integer', options: ['unsigned' => true], nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(length: 10)]
    private string $level = 'info';

    #[ORM\Column(length: 191)]
    private string $title = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(name: 'is_read', type: 'smallint')]
    private int $isRead = 0;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public static function create(string $level, string $title, ?string $message = null, ?string $url = null, ?int $userId = null, ?int $centreId = null): self
    {
        $a = new self();
        $a->level = $level;
        $a->title = $title;
        $a->message = $message;
        $a->url = $url;
        $a->userId = $userId;
        $a->centreId = $centreId;
        $a->createdAt = new \DateTimeImmutable();
        return $a;
    }

    public function getId(): ?int { return $this->id; }
    public function getCentreId(): ?int { return $this->centreId; }
    public function getUserId(): ?int { return $this->userId; }
    public function getLevel(): string { return $this->level; }
    public function getTitle(): string { return $this->title; }
    public function getMessage(): ?string { return $this->message; }
    public function getUrl(): ?string { return $this->url; }
    public function isRead(): bool { return $this->isRead === 1; }
    public function setIsRead(bool $v): self { $this->isRead = $v ? 1 : 0; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
}
