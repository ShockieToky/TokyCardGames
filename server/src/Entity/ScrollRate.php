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

    public function getScroll(): ?Scroll
    {
        return $this->scroll;
    }

    public function setScroll(Scroll $scroll): self
    {
        $this->scroll = $scroll;
        return $this;
    }

    public function getStar(): ?int
    {
        return $this->star;
    }

    public function setStar(int $star): self
    {
        $this->star = $star;
        return $this;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(float $rate): self
    {
        $this->rate = $rate;
        return $this;
    }
}
