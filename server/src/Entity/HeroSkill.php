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
    
    // Autres propriétés de l'entité HeroSkill
    
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