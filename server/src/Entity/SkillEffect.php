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

    public function getSkill(): ?HeroSkill
    {
        return $this->skill;
    }

    public function setSkill(?HeroSkill $skill): self
    {
        $this->skill = $skill;
        return $this;
    }

    public function getEffectType(): string
    {
        return $this->effect_type;
    }

    public function setEffectType(string $effect_type): self
    {
        $this->effect_type = $effect_type;
        return $this;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getChance(): int
    {
        return $this->chance;
    }

    public function setChance(int $chance): self
    {
        $this->chance = $chance;
        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    public function getScaleOn(): string
    {
        return $this->scale_on;
    }

    public function setScaleOn(string $scale_on): self
    {
        $this->scale_on = $scale_on;
        return $this;
    }

    public function getTargetSide(): string
    {
        return $this->target_side;
    }

    public function setTargetSide(string $target_side): self
    {
        $this->target_side = $target_side;
        return $this;
    }

    public function isCumulative(): bool
    {
        return $this->cumulative;
    }

    public function setCumulative(bool $cumulative): self
    {
        $this->cumulative = $cumulative;
        return $this;
    }
}
