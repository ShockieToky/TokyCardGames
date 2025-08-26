<?php

namespace App\Entity;

use App\Repository\HeroRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HeroRepository::class)]
class Hero
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $HP;

    #[ORM\Column(type: 'integer')]
    private int $DEF;

    #[ORM\Column(type: 'integer')]
    private int $ATK;

    #[ORM\Column(type: 'integer')]
    private int $VIT;

    #[ORM\Column(type: 'integer')]
    private int $RES;

    #[ORM\Column(type: 'integer')]
    private int $star;

    #[ORM\Column(type: 'integer')]
    private int $type;

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

    public function getHP(): ?int
    {
        return $this->HP;
    }

    public function setHP(int $HP): self
    {
        $this->HP = $HP;
        return $this;
    }

    public function getDEF(): ?int
    {
        return $this->DEF;
    }

    public function setDEF(int $DEF): self
    {
        $this->DEF = $DEF;
        return $this;
    }

    public function getATK(): ?int
    {
        return $this->ATK;
    }

    public function setATK(int $ATK): self
    {
        $this->ATK = $ATK;
        return $this;
    }

    public function getVIT(): ?int
    {
        return $this->VIT;
    }

    public function setVIT(int $VIT): self
    {
        $this->VIT = $VIT;
        return $this;
    }

    public function getRES(): ?int
    {
        return $this->RES;
    }

    public function setRES(int $RES): self
    {
        $this->RES = $RES;
        return $this;
    }

    public function getStar(): ?int
    {
        return $this->star;
    }

    public function setStar(int $star): self
    {
        $this->star = $star;
        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;
        return $this;
    }
}