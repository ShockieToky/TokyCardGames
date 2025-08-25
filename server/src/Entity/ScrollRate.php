<?php

namespace App\Entity;

use App\Repository\ScrollRateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScrollRateRepository::class)]
class ScrollRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Scroll::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Scroll $scroll = null;

    #[ORM\Column(type: 'integer')]
    private int $star;

    #[ORM\Column(type: 'float')]
    private float $rate;

    public function getId(): ?int
    {
        return $this->id;
    }
}
