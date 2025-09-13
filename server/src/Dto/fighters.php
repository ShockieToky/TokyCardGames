<?php

class Fighter
{
    public int $id;
    public int $heroId;
    public string $name;
    public int $hp;
    public int $maxHp;
    public int $attack;
    public int $defense;
    public int $speed;
    public int $resistance;
    public int $star;
    public string $type;
    public bool $isAlive;
    public string $team;
    public array $statusEffects;
    public array $cooldowns;

    public static function createFromHero(Hero $hero, int $fighterId, string $team): self
    {
        $fighter = new self();
        $fighter->id = $fighterId;
        $fighter->heroId = $hero->getId();
        $fighter->name = $hero->getName();
        $fighter->hp = $hero->getHP();
        $fighter->maxHp = $hero->getHP();
        $fighter->attack = $hero->getATK();
        $fighter->defense = $hero->getDEF();
        $fighter->speed = $hero->getVIT();
        $fighter->resistance = $hero->getRES();
        $fighter->star = $hero->getStar();
        $fighter->type = $hero->getType();
        $fighter->isAlive = true;
        $fighter->team = $team;
        $fighter->statusEffects = [];
        $fighter->cooldowns = [];
        return $fighter;
    }
}