<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'settings')]
class Setting
{
    #[ORM\Id]
    #[ORM\Column(name: '`key`', length: 100)]
    private string $key = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $value = null;

    public function getKey(): string { return $this->key; }
    public function setKey(string $v): self { $this->key = $v; return $this; }
    public function getValue(): ?string { return $this->value; }
    public function setValue(?string $v): self { $this->value = $v; return $this; }
}
