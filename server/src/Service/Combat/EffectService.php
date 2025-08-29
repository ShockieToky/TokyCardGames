<?php

namespace App\Service\Combat;

class EffectService
{
    /**
     * Applique un buff d'augmentation des PV (10% des PV max).
     */
    public function buffHp(array &$target): void
    {
        $bonus = intval($target['max_hp'] * 0.10);
        $target['hp'] += $bonus;
    }

    /**
     * Applique un buff d'augmentation de la défense (10% de la défense max).
     */
    public function buffDefense(array &$target): void
    {
        $bonus = intval($target['max_defense'] * 0.10);
        $target['defense'] += $bonus;
    }

    /**
     * Applique un buff d'augmentation de l'attaque (10% de l'attaque max).
     */
    public function buffAttack(array &$target): void
    {
        $bonus = intval($target['max_attack'] * 0.10);
        $target['attack'] += $bonus;
    }

    /**
     * Applique un buff d'augmentation de la vitesse (15% de la vitesse max).
     */
    public function buffSpeed(array &$target): void
    {
        $bonus = intval($target['max_speed'] * 0.15);
        $target['speed'] += $bonus;
    }

    /**
     * Applique un buff d'augmentation de la résistance (+25).
     */
    public function buffResistance(array &$target): void
    {
        $target['resistance'] += 25;
    }
}