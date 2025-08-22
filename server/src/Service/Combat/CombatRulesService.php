<?php

namespace App\Service\Combat;

use App\Entity\Hero;

class CombatRulesService
{
    /**
     * Détermine l'ordre des héros selon la vitesse
     */
    public function determineTurnOrder(array $heroes): array
    {
        usort($heroes, fn(Hero $a, Hero $b) => $b->getSpeed() <=> $a->getSpeed());
        return $heroes;
    }

    /**
     * Applique un buff ou debuff sur un héros
     */
    public function applyStatModifier(Hero $target, string $stat, float $value, bool $isBuff = true): void
    {
        switch ($stat) {
            case 'hp':
                $target->setHp($target->getHp() * (1 + $value / 100));
                break;
            case 'attack':
                $modifier = $isBuff ? 0.10 : -0.10;
                $target->setAttack($target->getAttack() * (1 + $modifier));
                break;
            case 'defense':
                $modifier = $isBuff ? 0.10 : -0.15;
                $target->setDefense($target->getDefense() * (1 + $modifier));
                break;
            case 'speed':
                $modifier = $isBuff ? 0.15 : -0.15;
                $target->setSpeed($target->getSpeed() * (1 + $modifier));
                break;
            case 'resistance':
                $modifier = $isBuff ? 25 : -20;
                $target->setResistance($target->getResistance() + $modifier);
                break;
        }
    }

    /**
     * Applique un effet positif
     */
    public function applyPositiveEffect(Hero $caster, Hero $target, string $effect, float $value = 0): void
    {
        switch ($effect) {
            case 'shield':
                $target->setShield($target->getHp() * ($value / 100));
                break;
            case 'protection':
                $target->setProtected(true);
                break;
            case 'lifeSteal':
                $caster->setHp(min($caster->getMaxHp(), $caster->getHp() + $value));
                break;
            case 'counter':
                $target->setCounterPercent($value);
                break;
            case 'revive':
                $target->setRevivePercent($value);
                break;
        }
    }

    /**
     * Applique un effet négatif
     */
    public function applyNegativeEffect(Hero $target, string $effect, int $turns = 1, float $value = 0): void
    {
        switch ($effect) {
            case 'stun':
                $target->setStunned($turns);
                break;
            case 'silence':
                $target->setSilenced($turns);
                break;
            case 'passiveBlock':
                $target->setPassiveBlocked($turns);
                break;
            case 'blockBuff':
                $target->setBuffBlocked(true);
                break;
            case 'damageOverTime':
                $target->setDotPercent($value);
                break;
            case 'taunt':
                $target->setProvoked(true);
                break;
            case 'deadlyHeal':
                $target->setDeadlyHeal(true);
                break;
        }
    }

    /**
     * Calcul des dégâts selon la formule :
     * dmg = attaque de l'attaquant x multiplicateur compétence x (1000 / (1000 + défense du défenseur))
     */
    public function calculateDamage(Hero $attacker, Hero $defender, float $skillMultiplier): int
    {
        $attack = $attacker->getAttack();
        $defense = $defender->getDefense();

        // Formule officielle
        $damage = $attack * $skillMultiplier * (1000 / (1000 + $defense));

        // Appliquer le bouclier
        if ($defender->getShield() > 0) {
            $shield = $defender->getShield();
            if ($damage >= $shield) {
                $damage -= $shield;
                $defender->setShield(0);
            } else {
                $defender->setShield($shield - $damage);
                $damage = 0;
            }
        }

        // Arrondi à l'entier supérieur pour éviter les dégâts décimaux
        return (int) ceil(max(0, $damage));
    }
}