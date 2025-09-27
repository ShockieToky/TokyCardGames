<?php

namespace App\Entity;

use App\Repository\ArenaDefenseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArenaDefenseRepository::class)]
class ArenaDefense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'arenaDefenses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?bool $isActive = false;

    // Correction : UserCollection au lieu de UserHero
    #[ORM\ManyToMany(targetEntity: UserCollection::class)]
    #[ORM\JoinTable(name: 'arena_defense_heroes')]
    private Collection $heroes;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->heroes = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, UserCollection>
     */
    public function getHeroes(): Collection
    {
        return $this->heroes;
    }

    public function addHero(UserCollection $hero): static
    {
        if (!$this->heroes->contains($hero)) {
            $this->heroes->add($hero);
        }

        return $this;
    }

    // Correction : UserCollection au lieu de UserHero
    public function removeHero(UserCollection $hero): static
    {
        $this->heroes->removeElement($hero);

        return $this;
    }

    /**
     * Vérifie si la défense a exactement 4 héros
     */
    public function isValid(): bool
    {
        return $this->heroes->count() === 4;
    }

    /**
     * Vérifie si tous les héros appartiennent à l'utilisateur
     */
    public function heroesOwnedByUser(): bool
    {
        foreach ($this->heroes as $heroEntry) {
            if ($heroEntry->getUser()->getId() !== $this->user->getId()) {
                return false;
            }
        }
        return true;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Met à jour la date de dernière modification
     */
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}