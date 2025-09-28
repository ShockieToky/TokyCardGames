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

    /**
     * Type d'effet (buff_attack, debuff_defense, stun, shield, etc.)
     */
    #[ORM\Column(type: 'string', length: 100)]
    private string $effect_type = 'buff_attack';

    /**
     * Valeur de l'effet (pourcentage, montant fixe, etc.)
     */
    #[ORM\Column(type: 'float')]
    private float $value = 0.0;

    /**
     * Probabilité d'appliquer l'effet (en pourcentage)
     */
    #[ORM\Column(type: 'integer')]
    private int $chance = 100;

    /**
     * Durée de l'effet en tours
     */
    #[ORM\Column(type: 'integer')]
    private int $duration = 2;

    /**
     * Mise à l'échelle de l'effet (JSON format)
     * Exemple : {"attack": 0.5, "defense": 0.2}
     */
    #[ORM\Column(type: 'text')]
    private string $scale_on = '{}';

    /**
     * Cible de l'effet : 'self', 'ally', 'enemy'
     */
    #[ORM\Column(type: 'string', length: 50)]
    private string $target_side = 'enemy';

    /**
     * Si l'effet peut se cumuler
     */
    #[ORM\Column(type: 'boolean')]
    private bool $cumulative = false;

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
    
    /**
     * Renvoie une représentation lisible de l'effet
     */
    public function getDescription(): string
    {
        $description = $this->getEffectTypeDescription();
        
        $valueStr = number_format($this->value, 1);
        if ($this->effect_type === 'buff_attack' || $this->effect_type === 'buff_defense') {
            $description .= " (+$valueStr%)";
        } elseif ($this->effect_type === 'debuff_attack' || $this->effect_type === 'debuff_defense') {
            $description .= " (-$valueStr%)";
        } elseif ($this->effect_type === 'damage_over_time' || $this->effect_type === 'lifesteal') {
            $description .= " ({$this->value}%)";
        } elseif ($this->effect_type === 'shield') {
            $description .= " ({$this->value}% PV)";
        }
        
        if ($this->duration > 1) {
            $description .= " pendant {$this->duration} tours";
        }
        
        if ($this->chance < 100) {
            $description .= " ({$this->chance}%)";
        }
        
        return $description;
    }
    
    /**
     * Convertit le type d'effet technique en description lisible
     */
    private function getEffectTypeDescription(): string
    {
        return match ($this->effect_type) {
            'buff_attack' => 'Augmente l\'attaque',
            'buff_defense' => 'Augmente la défense',
            'buff_speed' => 'Augmente la vitesse',
            'buff_resistance' => 'Augmente la résistance',
            'buff_hp' => 'Augmente les PV max',
            
            'debuff_attack' => 'Réduit l\'attaque',
            'debuff_defense' => 'Réduit la défense',
            'debuff_speed' => 'Réduit la vitesse',
            'debuff_resistance' => 'Réduit la résistance',
            
            'stun' => 'Étourdit la cible',
            'silence' => 'Réduit la cible au silence',
            'freeze' => 'Gèle la cible',
            'nullify' => 'Annule les passifs',
            'blocker' => 'Bloque les effets des alliés',
            'damage_over_time' => 'Inflige des dégâts continus',
            'heal_reverse' => 'Inverse les soins en dégâts',
            
            'shield' => 'Crée un bouclier',
            'protection' => 'Protège un allié',
            'lifesteal' => 'Vol de vie',
            'counter' => 'Contre-attaque',
            'resurrection' => 'Résurrection',
            'taunt' => 'Provocation',
            
            default => $this->effect_type,
        };
    }
}