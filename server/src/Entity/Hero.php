<?php

namespace App\Entity;

use App\Repository\HeroRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HeroRepository::class)]
class Hero
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $HP;

    #[ORM\Column(type: 'integer')]
    private int $DEF;

    #[ORM\Column(type: 'integer')]
    private int $ATK;

    #[ORM\Column(type: 'integer')]
    private int $VIT;

    #[ORM\Column(type: 'integer')]
    private int $RES;

    #[ORM\Column(type: 'integer')]
    private int $star;

    public function getId(): ?int
    {
        return $this->id;
    }
}
