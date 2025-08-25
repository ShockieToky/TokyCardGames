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
}
