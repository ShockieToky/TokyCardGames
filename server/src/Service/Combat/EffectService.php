<?php

namespace App\Service\Combat;

class EffectService
{
    /**
     * Crée un nouvel effet de statut.
     */
    public function createEffect(string $type, string $name, int $value, int $duration, ?string $stat = null): array
    {
        return [
            'id' => uniqid('effect_'),
            'type' => $type,
            'name' => $name,
            'value' => $value,
            'duration' => $duration,
            'stat' => $stat,
            'stackable' => $type !== 'buff' // Exemple: les buffs ne sont généralement pas cumulables
        ];
    }

    /**
     * MÉTHODES POUR LES BUFFS DE STATISTIQUES
     */
    
    /**
     * Augmentation PV: +10% de PV en plus de la cible
     */
    public function buffHp(array &$target, array &$logs, int $duration = 3, ?int $customValue = null): void
    {
        $bonus = $customValue ?? intval($target['maxHp'] * 0.10);
        $effect = $this->createEffect('buff', 'Augmentation PV', $bonus, $duration, 'hp');
        $this->applyEffect($target, $effect, $logs);
    }

    /**
     * Augmentation Défense: +10% de défense
     */
    public function buffDefense(array &$target, array &$logs, int $duration = 3, ?int $customValue = null): void
    {
        $bonus = $customValue ?? intval($target['defense'] * 0.10);
        $effect = $this->createEffect('buff', 'Défense renforcée', $bonus, $duration, 'defense');
        $this->applyEffect($target, $effect, $logs);
    }

    /**
     * Augmentation Attaque: +10% d'attaque
     */
    public function buffAttack(array &$target, array &$logs, int $duration = 3, ?int $customValue = null): void
    {
        $bonus = $customValue ?? intval($target['attack'] * 0.10);
        $effect = $this->createEffect('buff', 'Force accrue', $bonus, $duration, 'attack');
        $this->applyEffect($target, $effect, $logs);
    }

    /**
     * Augmentation Vitesse: +15% de vitesse
     */
    public function buffSpeed(array &$target, array &$logs, int $duration = 2, ?int $customValue = null): void
    {
        $bonus = $customValue ?? intval($target['speed'] * 0.15);
        $effect = $this->createEffect('buff', 'Vélocité', $bonus, $duration, 'speed');
        $this->applyEffect($target, $effect, $logs);
    }

    /**
     * Augmentation Résistance: +25 de résistance
     */
    public function buffResistance(array &$target, array &$logs, int $duration = 3, ?int $customValue = null): void
    {
        $bonus = $customValue ?? 25;
        $effect = $this->createEffect('buff', 'Résistance accrue', $bonus, $duration, 'resistance');
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * EFFETS SPÉCIAUX PLUS COMPLEXES
     */
    
    /**
     * Bouclier: Absorbe des dégâts en fonction des PV du lanceur
     */
    public function applyShield(array &$target, array &$caster, array &$logs, int $duration = 2, ?int $customValue = null): void
    {
        // Si customValue est fourni, utilisez-le, sinon calculez à partir des PV du lanceur
        $shieldValue = $customValue ?? intval($caster['maxHp'] * 0.20); // 20% des PV max par défaut
        
        $effect = $this->createEffect('shield', 'Bouclier', $shieldValue, $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Protection: Le lanceur prend les dégâts à la place de la cible
     */
    public function applyProtection(array &$target, array &$caster, array &$logs, int $duration = 2): void
    {
        // Stocke l'ID du protecteur
        $effect = $this->createEffect('protection', 'Protection', $caster['id'], $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Soif de sang: 15% des dégâts infligés sont récupérés en soin
     */
    public function applyLifesteal(array &$target, array &$logs, int $duration = 3, ?int $customValue = null): void
    {
        $lifestealPercent = $customValue ?? 15; // 15% par défaut
        $effect = $this->createEffect('lifesteal', 'Soif de sang', $lifestealPercent, $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Contre: Chance de contre-attaquer avec le sort 1
     */
    public function applyCounter(array &$target, array &$logs, int $duration = 3, ?int $customValue = null): void
    {
        $counterChance = $customValue ?? 25; // 25% par défaut
        $effect = $this->createEffect('counter', 'Contre', $counterChance, $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Sauvetage: Revient à la vie avec 15% des PV max
     * Note: Cet effet est particulier car il s'active à la mort
     */
    public function applyResurrection(array &$target, array &$logs, int $duration = 3, ?int $customValue = null): void
    {
        $resurrectionPercent = $customValue ?? 15; // 15% par défaut
        $effect = $this->createEffect('resurrection', 'Sauvetage', $resurrectionPercent, $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Méthode principale de traitement des effets, mise à jour pour gérer les nouveaux effets
     */
    public function processEffects(array &$fighters, array &$logs): void
    {
        foreach ($fighters as &$fighter) {
            if (!$fighter['isAlive']) {
                // Vérifier si le combattant a un effet de résurrection
                $this->checkForResurrection($fighter, $logs);
                continue;
            }

            $effectsToRemove = [];
            
            // Parcourir et appliquer chaque effet
            foreach ($fighter['statusEffects'] as $key => &$effect) {
                // Appliquer l'effet selon son type
                switch ($effect['type']) {
                    case 'poison':
                        $damage = $effect['value'];
                        $fighter['hp'] -= $damage;
                        $this->addCombatLog($logs, "{$fighter['name']} (#{$fighter['id']}) subit {$damage} dégâts de poison");
                        break;
                        
                    case 'regeneration':
                        $heal = $effect['value'];
                        $fighter['hp'] = min($fighter['hp'] + $heal, $fighter['maxHp']);
                        $this->addCombatLog($logs, "{$fighter['name']} (#{$fighter['id']}) récupère {$heal} points de vie");
                        break;
                        
                    // Les autres types d'effets sont traités à d'autres moments du combat
                    // (shield lors de la prise de dégâts, lifesteal lors de l'attaque, etc.)
                }
                
                // Réduire la durée de l'effet
                $effect['duration']--;
                
                // Vérifier si l'effet est terminé
                if ($effect['duration'] <= 0) {
                    $effectsToRemove[] = $key;
                    
                    // Pour les buffs, restaurer les stats originales
                    if ($effect['type'] === 'buff' && isset($effect['stat'])) {
                        $fighter[$effect['stat']] -= $effect['value'];
                        $this->addCombatLog($logs, "L'effet {$effect['name']} s'estompe pour {$fighter['name']} (#{$fighter['id']})");
                    }
                }
            }
            
            // Supprimer les effets terminés
            foreach ($effectsToRemove as $key) {
                unset($fighter['statusEffects'][$key]);
            }
            
            // Vérifier si le combattant est mort à cause des effets
            if ($fighter['hp'] <= 0) {
                $fighter['hp'] = 0;
                $fighter['isAlive'] = false;
                $this->addCombatLog($logs, "{$fighter['name']} (#{$fighter['id']}) est mort à cause des effets !");
                
                // Vérifier si le combattant a un effet de résurrection
                $this->checkForResurrection($fighter, $logs);
            }
        }
    }
    
    /**
     * Vérifie et applique l'effet de résurrection si présent
     */
    private function checkForResurrection(array &$fighter, array &$logs): void
    {
        if ($fighter['isAlive']) {
            return;
        }
        
        foreach ($fighter['statusEffects'] as $key => $effect) {
            if ($effect['type'] === 'resurrection') {
                // Activer la résurrection
                $resurrectionHp = intval($fighter['maxHp'] * ($effect['value'] / 100));
                $fighter['hp'] = $resurrectionHp;
                $fighter['isAlive'] = true;
                
                $this->addCombatLog($logs, "{$fighter['name']} (#{$fighter['id']}) revient à la vie avec {$resurrectionHp} PV grâce à Sauvetage !");
                
                // Supprimer l'effet de résurrection une fois utilisé
                unset($fighter['statusEffects'][$key]);
                return;
            }
        }
    }
    
    /**
     * Applique l'effet du bouclier lors de la prise de dégâts
     * Cette méthode doit être appelée par AttackService avant d'infliger des dégâts
     * 
     * @return int Les dégâts restants après absorption par le bouclier
     */
    public function applyShieldEffect(array &$target, int $damage, array &$logs): int
    {
        $remainingDamage = $damage;
        
        foreach ($target['statusEffects'] as &$effect) {
            if ($effect['type'] === 'shield' && $effect['value'] > 0) {
                $shieldAbsorb = min($effect['value'], $remainingDamage);
                $effect['value'] -= $shieldAbsorb;
                $remainingDamage -= $shieldAbsorb;
                
                $this->addCombatLog($logs, "Le bouclier de {$target['name']} (#{$target['id']}) absorbe {$shieldAbsorb} dégâts!");
                
                if ($effect['value'] <= 0) {
                    $this->addCombatLog($logs, "Le bouclier de {$target['name']} (#{$target['id']}) est détruit!");
                }
                
                if ($remainingDamage <= 0) {
                    break;
                }
            }
        }
        
        return max(0, $remainingDamage);
    }
    
    /**
     * Recherche un protecteur pour la cible
     * Cette méthode doit être appelée par AttackService avant d'infliger des dégâts
     * 
     * @return array|null Le protecteur si trouvé, null sinon
     */
    public function findProtector(array &$target, array &$fighters): ?array
    {
        foreach ($target['statusEffects'] as $effect) {
            if ($effect['type'] === 'protection') {
                $protectorId = $effect['value'];
                
                // Chercher le protecteur parmi les combattants
                foreach ($fighters as &$fighter) {
                    if ($fighter['id'] === $protectorId && $fighter['isAlive']) {
                        return $fighter;
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Applique l'effet de vol de vie après une attaque réussie
     * Cette méthode doit être appelée par AttackService après avoir infligé des dégâts
     */
    public function applyLifestealEffect(array &$attacker, int $damageDealt, array &$logs): void
    {
        foreach ($attacker['statusEffects'] as $effect) {
            if ($effect['type'] === 'lifesteal') {
                $healAmount = intval($damageDealt * ($effect['value'] / 100));
                
                if ($healAmount > 0) {
                    $attacker['hp'] = min($attacker['hp'] + $healAmount, $attacker['maxHp']);
                    $this->addCombatLog($logs, "{$attacker['name']} (#{$attacker['id']}) récupère {$healAmount} PV grâce à Soif de sang!");
                }
            }
        }
    }
    
    /**
     * Vérifie si le combattant doit contre-attaquer
     * Cette méthode doit être appelée par AttackService après une attaque subie
     * 
     * @return bool True si le combattant doit contre-attaquer
     */
    public function shouldCounterAttack(array &$target, array &$logs): bool
    {
        foreach ($target['statusEffects'] as $effect) {
            if ($effect['type'] === 'counter') {
                $counterChance = $effect['value'];
                
                if (rand(1, 100) <= $counterChance) {
                    $this->addCombatLog($logs, "{$target['name']} (#{$target['id']}) se prépare à contre-attaquer!");
                    return true;
                }
            }
        }
        
        return false;
    }

    private function addCombatLog(array &$logs, string $message): void
    {
        $logs[] = [
            'timestamp' => time(),
            'message' => $message
        ];
    }
}