<?php

namespace App\Entity;

use App\Repository\HeroSkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HeroSkillRepository::class)]
class HeroSkill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\Column(length: 255)]
    private string $name = '';
    
    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;
    
    #[ORM\Column(type: "float", nullable: true)]
    private ?float $multiplicator = null;
    
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $scaling = null;
    
    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $hitsNumber = 1;
    
    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $cooldown = 0;
    
    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $initialCooldown = 0;
    
    #[ORM\Column(type: "boolean", nullable: true)]
    private ?bool $isPassive = false;
    
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $targeting = null;
    
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $targetingTeam = null;
    
    #[ORM\Column(type: "boolean", nullable: true)]
    private ?bool $doesDamage = true;
    
    #[ORM\ManyToOne(targetEntity: Hero::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Hero $hero = null;
    
    #[ORM\OneToMany(mappedBy: 'skill', targetEntity: LinkSkillEffect::class, orphanRemoval: true)]
    private Collection $effectLinks;
    
    public function __construct()
    {
        $this->effectLinks = new ArrayCollection();
    }
    
    public function getId(): ?int
    {
        return $this->id;
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
    
    public function getDescription(): ?string
    {
        return $this->description;
    }
    
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
    
    public function getMultiplicator(): ?float
    {
        return $this->multiplicator;
    }
    
    public function setMultiplicator(?float $multiplicator): self
    {
        $this->multiplicator = $multiplicator;
        return $this;
    }
    
    public function getScaling(): ?string
    {
        return $this->scaling;
    }
    
    public function setScaling(?string $scaling): self
    {
        $this->scaling = $scaling;
        return $this;
    }
    
    public function getHitsNumber(): ?int
    {
        return $this->hitsNumber;
    }
    
    public function setHitsNumber(?int $hitsNumber): self
    {
        $this->hitsNumber = $hitsNumber;
        return $this;
    }
    
    public function getCooldown(): ?int
    {
        return $this->cooldown;
    }
    
    public function setCooldown(?int $cooldown): self
    {
        $this->cooldown = $cooldown;
        return $this;
    }
    
    public function getInitialCooldown(): ?int
    {
        return $this->initialCooldown;
    }
    
    public function setInitialCooldown(?int $initialCooldown): self
    {
        $this->initialCooldown = $initialCooldown;
        return $this;
    }
    
    public function getIsPassive(): ?bool
    {
        return $this->isPassive;
    }
    
    public function setIsPassive(?bool $isPassive): self
    {
        $this->isPassive = $isPassive;
        return $this;
    }
    
    public function getTargeting(): ?string
    {
        return $this->targeting;
    }
    
    public function setTargeting(?string $targeting): self
    {
        $this->targeting = $targeting;
        return $this;
    }
    
    public function getTargetingTeam(): ?string
    {
        return $this->targetingTeam;
    }
    
    public function setTargetingTeam(?string $targetingTeam): self
    {
        $this->targetingTeam = $targetingTeam;
        return $this;
    }
    
    public function getDoesDamage(): ?bool
    {
        return $this->doesDamage;
    }
    
    public function setDoesDamage(?bool $doesDamage): self
    {
        $this->doesDamage = $doesDamage;
        return $this;
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
    
    /**
     * @return Collection<int, LinkSkillEffect>
     */
    public function getEffectLinks(): Collection
    {
        return $this->effectLinks;
    }

    public function addEffectLink(LinkSkillEffect $effectLink): self
    {
        if (!$this->effectLinks->contains($effectLink)) {
            $this->effectLinks->add($effectLink);
            $effectLink->setSkill($this);
        }
        
        return $this;
    }

    public function removeEffectLink(LinkSkillEffect $effectLink): self
    {
        if ($this->effectLinks->removeElement($effectLink)) {
            // Set the owning side to null if necessary
            if ($effectLink->getSkill() === $this) {
                $effectLink->setSkill(null);
            }
        }
        
        return $this;
    }
}