<?php

namespace App\Entity;

use App\Repository\UserScrollRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserScrollRepository::class)]
class UserScroll
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Scroll::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Scroll $scroll = null;

    #[ORM\Column(type: 'integer')]
    private int $quantity = 0;

    public function getId(): ?int
    {
        return $this->id;
    }
}
