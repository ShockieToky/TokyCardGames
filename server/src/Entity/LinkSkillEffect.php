<?php

namespace App\Entity;

use App\Repository\LinkSkillEffectRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LinkSkillEffectRepository::class)]
class LinkSkillEffect
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    /**
     * La compétence associée à cet effet
     */
    #[ORM\ManyToOne(targetEntity: HeroSkill::class, inversedBy: 'effectLinks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?HeroSkill $skill = null;
    
    /**
     * L'effet appliqué
     */
    #[ORM\ManyToOne(targetEntity: SkillEffect::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?SkillEffect $effect = null;
    
    /**
     * Durée de l'effet en tours
     */
    #[ORM\Column(type: 'integer', options: ["default" => 1])]
    private int $duration = 1;
    
    /**
     * Précision (chance d'appliquer l'effet) en pourcentage
     */
    #[ORM\Column(type: 'integer', options: ["default" => 100])]
    private int $accuracy = 100;
    
    /**
     * Valeur de l'effet (pourcentage ou valeur fixe)
     */
    #[ORM\Column(type: 'float', options: ["default" => 0])]
    private float $value = 0.0;
    
    /**
     * Date de création
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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
    
    public function getEffect(): ?SkillEffect
    {
        return $this->effect;
    }
    
    public function setEffect(?SkillEffect $effect): self
    {
        $this->effect = $effect;
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
    
    public function getAccuracy(): int
    {
        return $this->accuracy;
    }
    
    public function setAccuracy(int $accuracy): self
    {
        $this->accuracy = $accuracy;
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
    
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    
    /**
     * Obtenir une description complète de l'effet avec ses paramètres
     */
    public function getFullDescription(): string
    {
        $effect = $this->effect;
        if (!$effect) {
            return 'Effet non défini';
        }
        
        $description = $effect->getName();
        
        if ($this->value != 0) {
            $description .= ' (' . ($this->value > 0 ? '+' : '') . $this->value . ')';
        }
        
        if ($this->duration > 1) {
            $description .= ' pendant ' . $this->duration . ' tours';
        }
        
        if ($this->accuracy < 100) {
            $description .= ' (' . $this->accuracy . '% de chance)';
        }
        
        return $description;
    }
}