<?php

namespace App\Entity;

use App\Repository\HeroSkillRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HeroSkillRepository::class)]
class HeroSkill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Hero::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Hero $hero = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string')]
    private string $description;

    #[ORM\Column(type: 'float')]
    private float $multiplicator;

    #[ORM\Column(type: 'text')]
    private string $scaling;

    #[ORM\Column(type: 'integer')]
    private int $hits_number;

    #[ORM\Column(type: 'integer')]
    private int $cooldown;

    #[ORM\Column(type: 'integer')]
    private int $initial_cooldown;

    #[ORM\Column(type: 'boolean')]
    private bool $is_passive;

    #[ORM\Column(type: 'text')]
    private string $targeting;

    #[ORM\Column(type: 'text')]
    private string $targeting_team;

    #[ORM\Column(type: 'boolean')]
    private bool $does_damage;

    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getHero(): ?Hero
    {
        return $this->hero;
    }

    public function setHero(?Hero $hero): self
    {
        $this->hero = $hero;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getMultiplicator(): float
    {
        return $this->multiplicator;
    }

    public function setMultiplicator(float $multiplicator): self
    {
        $this->multiplicator = $multiplicator;
        return $this;
    }

    public function getScaling(): string
    {
        return $this->scaling;
    }

    public function setScaling(string $scaling): self
    {
        $this->scaling = $scaling;
        return $this;
    }

    public function getHitsNumber(): int
    {
        return $this->hits_number;
    }

    public function setHitsNumber(int $hits_number): self
    {
        $this->hits_number = $hits_number;
        return $this;
    }

    public function getCooldown(): int
    {
        return $this->cooldown;
    }

    public function setCooldown(int $cooldown): self
    {
        $this->cooldown = $cooldown;
        return $this;
    }

    public function getInitialCooldown(): int
    {
        return $this->initial_cooldown;
    }

    public function setInitialCooldown(int $initial_cooldown): self
    {
        $this->initial_cooldown = $initial_cooldown;
        return $this;
    }

    public function getIsPassive(): bool
    {
        return $this->is_passive;
    }

    public function setIsPassive(bool $is_passive): self
    {
        $this->is_passive = $is_passive;
        return $this;
    }

    public function getTargeting(): string
    {
        return $this->targeting;
    }

    public function setTargeting(string $targeting): self
    {
        $this->targeting = $targeting;
        return $this;
    }

    public function getTargetingTeam(): string
    {
        return $this->targeting_team;
    }

    public function setTargetingTeam(string $targeting_team): self
    {
        $this->targeting_team = $targeting_team;
        return $this;
    }

    public function getDoesDamage(): bool
    {
        return $this->does_damage;
    }

    public function setDoesDamage(bool $does_damage): self
    {
        $this->does_damage = $does_damage;
        return $this;
    }
}
