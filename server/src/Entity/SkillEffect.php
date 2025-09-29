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

    /**
     * Nom de l'effet (ex: "Augmentation d'attaque", "Étourdissement", etc.)
     */
    #[ORM\Column(type: 'string', length: 100)]
    private string $name = '';

    /**
     * Description détaillée de l'effet
     */
    #[ORM\Column(type: 'text')]
    private string $description = '';

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Représentation sous forme de chaîne
     */
    public function __toString(): string
    {
        return $this->name;
    }
}