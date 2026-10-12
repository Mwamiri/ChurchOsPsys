<?php

namespace App\Entity;

use App\Repository\CentreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CentreRepository::class)]
#[ORM\Table(name: 'centres')]
#[ORM\HasLifecycleCallbacks]
class Centre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', nullable: true)]
    private ?Centre $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $children;

    #[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
    private int $level = 2;

    #[ORM\Column(length: 191)]
    private string $name = '';

    #[ORM\Column(length: 30, nullable: true, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 191, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(name: 'header_text', type: 'text', nullable: true)]
    private ?string $headerText = null;

    #[ORM\Column(name: 'footer_text', type: 'text', nullable: true)]
    private ?string $footerText = null;

    #[ORM\Column(name: 'is_active', type: 'smallint')]
    private int $isActive = 1;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

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

    public function getId(): ?int { return $this->id; }
    public function getParent(): ?Centre { return $this->parent; }
    public function setParent(?Centre $v): self { $this->parent = $v; return $this; }
    public function getChildren(): Collection { return $this->children; }
    public function getLevel(): int { return $this->level; }
    public function setLevel(int $v): self { $this->level = $v; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $v): self { $this->name = $v; return $this; }
    public function getCode(): ?string { return $this->code; }
    public function setCode(?string $v): self { $this->code = $v ?: null; return $this; }
    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $v): self { $this->location = $v; return $this; }
    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $v): self { $this->phone = $v; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $v): self { $this->email = $v; return $this; }
    public function getLogo(): ?string { return $this->logo; }
    public function setLogo(?string $v): self { $this->logo = $v; return $this; }
    public function getHeaderText(): ?string { return $this->headerText; }
    public function setHeaderText(?string $v): self { $this->headerText = $v; return $this; }
    public function getFooterText(): ?string { return $this->footerText; }
    public function setFooterText(?string $v): self { $this->footerText = $v; return $this; }
    public function isActive(): bool { return $this->isActive === 1; }
    public function setIsActive(bool $v): self { $this->isActive = $v ? 1 : 0; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function getFullPath(): string
    {
        $parts = [$this->name];
        $p = $this->parent;
        while ($p) {
            array_unshift($parts, $p->getName());
            $p = $p->getParent();
        }
        return implode(' â€º ', $parts);
    }

    /** Collect all descendant IDs including self */
    public function subtreeIds(): array
    {
        $ids = [$this->id];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->subtreeIds());
        }
        return $ids;
    }
}
