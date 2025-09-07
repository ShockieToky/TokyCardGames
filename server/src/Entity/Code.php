<?php

namespace App\Entity;

use App\Repository\CodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CodeRepository::class)]
class Code
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $expirationDate = null;

    #[ORM\Column(type: 'integer')]
    private ?int $scrollId = null;

    #[ORM\Column(type: 'integer')]
    private ?int $scrollCount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getExpirationDate(): ?\DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(\DateTimeInterface $expirationDate): self
    {
        $this->expirationDate = $expirationDate;
        return $this;
    }

    public function getScrollId(): ?int
    {
        return $this->scrollId;
    }

    public function setScrollId(int $scrollId): self
    {
        $this->scrollId = $scrollId;
        return $this;
    }

    public function getScrollCount(): ?int
    {
        return $this->scrollCount;
    }

    public function setScrollCount(int $scrollCount): self
    {
        $this->scrollCount = $scrollCount;
        return $this;
    }
}

?>