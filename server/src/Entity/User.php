<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $pseudo = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private bool $is_admin = false;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ArenaDefense::class, orphanRemoval: true)]
    private Collection $arenaDefenses;

    public function __construct()
    {
        $this->arenaDefenses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): self
    {
        $this->pseudo = $pseudo;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function setIsAdmin(bool $is_admin): self
    {
        $this->is_admin = $is_admin;
        return $this;
    }

    /**
     * @return Collection<int, ArenaDefense>
     */
    public function getArenaDefenses(): Collection
    {
        return $this->arenaDefenses;
    }

    public function addArenaDefense(ArenaDefense $arenaDefense): self
    {
        if (!$this->arenaDefenses->contains($arenaDefense)) {
            $this->arenaDefenses->add($arenaDefense);
            $arenaDefense->setUser($this);
        }

        return $this;
    }

    public function removeArenaDefense(ArenaDefense $arenaDefense): self
    {
        if ($this->arenaDefenses->removeElement($arenaDefense)) {
            // Set the owning side to null (unless already changed)
            if ($arenaDefense->getUser() === $this) {
                $arenaDefense->setUser(null);
            }
        }

        return $this;
    }

    /**
     * Récupère la défense d'arène active de l'utilisateur
     */
    public function getActiveArenaDefense(): ?ArenaDefense
    {
        foreach ($this->arenaDefenses as $defense) {
            if ($defense->isActive()) {
                return $defense;
            }
        }
        return null;
    }

    /**
     * Vérifie si l'utilisateur a atteint la limite de défenses d'arène (4)
     */
    public function hasMaxArenaDefenses(): bool
    {
        return $this->arenaDefenses->count() >= 4;
    }

    // Méthodes requises par UserInterface
    public function getRoles(): array
    {
        return $this->is_admin ? ['ROLE_ADMIN', 'ROLE_USER'] : ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // Rien à faire ici
    }

    public function getUserIdentifier(): string
    {
        return $this->pseudo;
    }
}