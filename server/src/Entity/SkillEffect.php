<?php

namespace App\Entity;

use App\Repository\SkillEffectRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkillEffectRepository::class)]
class SkillEffect
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: HeroSkill::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?HeroSkill $skill = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $effect_type;

    #[ORM\Column(type: 'float')]
    private float $value;

    #[ORM\Column(type: 'integer')]
    private int $chance;

    #[ORM\Column(type: 'integer')]
    private int $duration;

    #[ORM\Column(type: 'text')]
    private int $scale_on;

    #[ORM\Column(type: 'text')]
    private int $target_side;

    #[ORM\Column(type: 'boolean')]
    private int $cumulative;

    public function getId(): ?int
    {
        return $this->id;
    }
}
