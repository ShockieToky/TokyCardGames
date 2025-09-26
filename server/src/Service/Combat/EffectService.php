<?php

namespace App\Service\Combat;

use App\Entity\SkillEffect;
use Doctrine\ORM\EntityManagerInterface;

class EffectService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Applique les effets d'un skill depuis la BDD
     */
    public function applySkillEffects($skill, array &$attacker, array &$target, array &$logs, array &$allFighters): void
    {
        // Récupérer les effets du skill depuis la BDD
        $skillEffectRepository = $this->entityManager->getRepository(SkillEffect::class);
        $skillEffects = $skillEffectRepository->findBy(['skill' => $skill]);
        
        foreach ($skillEffects as $skillEffect) {
            // Vérifier la chance de proc
            $chance = $skillEffect->getChance();
            if (rand(1, 100) > $chance) {
                $this->addCombatLog($logs, "L'effet {$skillEffect->getEffectType()} de {$skill->getName()} ne se déclenche pas ({$chance}% de chance)");
                continue; // L'effet ne se déclenche pas
            }
            
            // Déterminer la cible selon target_side
            $effectTarget = null;
            $targetSide = $skillEffect->getTargetSide();
            
            switch ($targetSide) {
                case 'self':
                    $effectTarget = &$attacker;
                    break;
                case 'ally':
                    // Pour les skills ciblant un allié, utiliser un allié aléatoire vivant
                    $allies = array_filter($allFighters, function($f) use ($attacker) {
                        return $f['team'] === $attacker['team'] && $f['isAlive'] && $f['id'] !== $attacker['id'];
                    });
                    if (!empty($allies)) {
                        $randomAlly = array_rand($allies);
                        $effectTarget = &$allFighters[$randomAlly];
                    } else {
                        // Si pas d'allié disponible, se cibler soi-même
                        $effectTarget = &$attacker;
                    }
                    break;
                case 'enemy':
                default:
                    $effectTarget = &$target;
                    break;
            }
            
            if (!$effectTarget) {
                $this->addCombatLog($logs, "Aucune cible valide pour l'effet {$skillEffect->getEffectType()}");
                continue;
            }
            
            // Calculer la valeur de l'effet (avec scaling si défini)
            $value = $skillEffect->getValue();
            $scaleOnJson = $skillEffect->getScaleOn();
            
            if ($scaleOnJson && $scaleOnJson !== '{}') {
                $scaleOn = json_decode($scaleOnJson, true);
                
                if ($scaleOn && is_array($scaleOn)) {
                    $scaledValue = 0;
                    foreach ($scaleOn as $stat => $coefficient) {
                        switch (strtolower($stat)) {
                            case 'attack':
                            case 'atk':
                                $scaledValue += $attacker['attack'] * $coefficient;
                                break;
                            case 'defense':
                            case 'def':
                                $scaledValue += $attacker['defense'] * $coefficient;
                                break;
                            case 'speed':
                            case 'vit':
                                $scaledValue += $attacker['speed'] * $coefficient;
                                break;
                            case 'resistance':
                            case 'res':
                                $scaledValue += $attacker['resistance'] * $coefficient;
                                break;
                            case 'hp':
                                $scaledValue += $attacker['maxHp'] * $coefficient;
                                break;
                        }
                    }
                    if ($scaledValue > 0) {
                        $value = $scaledValue;
                    }
                }
            }
            
            // Appliquer l'effet
            $this->addCombatLog($logs, "Application de l'effet {$skillEffect->getEffectType()} avec valeur {$value} pour {$skillEffect->getDuration()} tours");
            
            $this->applyEffectByType(
                $skillEffect->getEffectType(),
                $effectTarget,
                $logs,
                $attacker,
                $value,
                $skillEffect->getDuration(),
                $scaleOnJson
            );
        }
    }

    public function applyEffectByType(string $effectType, array &$target, array &$logs, array &$caster = null, $value = null, int $duration = 1, ?string $scaleOn = null): void
    {
        switch ($effectType) {
            // BUFFS DE STATISTIQUES
            case 'buff_hp':
                $this->buffHp($target, $logs, $duration, $value);
                break;
            case 'buff_defense':
                $this->buffDefense($target, $logs, $duration, $value);
                break;
            case 'buff_attack':
                $this->buffAttack($target, $logs, $duration, $value);
                break;
            case 'buff_speed':
                $this->buffSpeed($target, $logs, $duration, $value);
                break;
            case 'buff_resistance':
                $this->buffResistance($target, $logs, $duration, $value);
                break;
                
            // DÉBUFFS DE STATISTIQUES
            case 'debuff_defense':
                $this->debuffDefense($target, $logs, $duration, $value);
                break;
            case 'debuff_speed':
                $this->debuffSpeed($target, $logs, $duration, $value);
                break;
            case 'debuff_attack':
                $this->debuffAttack($target, $logs, $duration, $value);
                break;
            case 'debuff_resistance':
                $this->debuffResistance($target, $logs, $duration, $value);
                break;
                
            // EFFETS SPÉCIAUX POSITIFS
            case 'shield':
                if ($caster) {
                    $this->applyShield($target, $caster, $logs, $duration, $value);
                }
                break;
            case 'protection':
                if ($caster) {
                    $this->applyProtection($target, $caster, $logs, $duration);
                }
                break;
            case 'lifesteal':
                $this->applyLifesteal($target, $logs, $duration, $value);
                break;
            case 'counter':
                $this->applyCounter($target, $logs, $duration, $value);
                break;
            case 'resurrection':
                $this->applyResurrection($target, $logs, $duration, $value);
                break;
                
            // EFFETS NÉGATIFS
            case 'stun':
                $this->applyStun($target, $logs, $duration);
                break;
            case 'silence':
                $this->applySilence($target, $logs, $duration);
                break;
            case 'nullify':
                $this->applyNullify($target, $logs, $duration);
                break;
            case 'blocker':
                $this->applyBlocker($target, $logs, $duration);
                break;
            case 'damage_over_time':
            case 'dot':
                $this->applyDamageOverTime($target, $logs, $duration, $value);
                break;
            case 'taunt':
                if ($caster) {
                    $this->applyTaunt($target, $caster, $logs, $duration);
                }
                break;
            case 'heal_reverse':
                $this->applyHealReverse($target, $logs, $duration);
                break;
            case 'freeze':
                $this->applyFreeze($target, $logs, $duration);
                break;
                
            default:
                $this->addCombatLog($logs, "Effet inconnu : {$effectType}");
                break;
        }
    }
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
            'stackable' => $type !== 'buff'
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
     * EFFETS SPÉCIAUX POSITIFS
     */
    
    /**
     * Bouclier: Absorbe des dégâts en fonction des PV du lanceur
     */
    public function applyShield(array &$target, array &$caster, array &$logs, int $duration = 2, ?int $customValue = null): void
    {
        $shieldValue = $customValue ?? intval($caster['maxHp'] * 0.20);
        
        $effect = $this->createEffect('shield', 'Bouclier', $shieldValue, $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Protection: Le lanceur prend les dégâts à la place de la cible
     */
    public function applyProtection(array &$target, array &$caster, array &$logs, int $duration = 2): void
    {
        $effect = $this->createEffect('protection', 'Protection', $caster['id'], $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Soif de sang: 15% des dégâts infligés sont récupérés en soin
     */
    public function applyLifesteal(array &$target, array &$logs, int $duration = 3, ?int $customValue = null): void
    {
        $lifestealPercent = $customValue ?? 15;
        $effect = $this->createEffect('lifesteal', 'Soif de sang', $lifestealPercent, $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Contre: Chance de contre-attaquer avec le sort 1
     */
    public function applyCounter(array &$target, array &$logs, int $duration = 3, ?int $customValue = null): void
    {
        $counterChance = $customValue ?? 25;
        $effect = $this->createEffect('counter', 'Contre', $counterChance, $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Sauvetage: Revient à la vie avec 15% des PV max
     */
    public function applyResurrection(array &$target, array &$logs, int $duration = 3, ?int $customValue = null): void
    {
        $resurrectionPercent = $customValue ?? 15;
        $effect = $this->createEffect('resurrection', 'Sauvetage', $resurrectionPercent, $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * EFFETS NÉGATIFS (DEBUFFS)
     */
    
    /**
     * Étourdissement: la cible ne peut pas attaquer pendant 1 tour
     */
    public function applyStun(array &$target, array &$logs, int $duration = 1): void
    {
        $effect = $this->createEffect('stun', 'Étourdissement', 1, $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Silence: la cible ne peut utiliser que son sort 1 pendant X tour
     */
    public function applySilence(array &$target, array &$logs, int $duration = 2): void
    {
        $effect = $this->createEffect('silence', 'Silence', 1, $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Annulation: la cible ne peut pas utiliser/proc son passif pendant X tour
     */
    public function applyNullify(array &$target, array &$logs, int $duration = 2): void
    {
        $effect = $this->createEffect('nullify', 'Annulation', 1, $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Bloqueur: la cible ne peut pas gagner d'effet de la part de ses alliés pendant X tour
     */
    public function applyBlocker(array &$target, array &$logs, int $duration = 2): void
    {
        $effect = $this->createEffect('blocker', 'Bloqueur', 1, $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Dégâts continus: 5% des PV de la cible en dégâts en début de tour pendant X tour
     */
    public function applyDamageOverTime(array &$target, array &$logs, int $duration = 3, ?int $customPercent = null): void
    {
        $damagePercent = $customPercent ?? 5; // 5% par défaut
        $effect = $this->createEffect('damage_over_time', 'Dégâts continus', $damagePercent, $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Provocation: la cible adverse doit attaquer le lanceur du sort pendant X tour
     */
    public function applyTaunt(array &$target, array &$caster, array &$logs, int $duration = 2): void
    {
        $effect = $this->createEffect('taunt', 'Provocation', $caster['id'], $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Soins Mortels: la cible adverse perd des PV si elle est soignée pendant X tour
     */
    public function applyHealReverse(array &$target, array &$logs, int $duration = 2): void
    {
        $effect = $this->createEffect('heal_reverse', 'Soins Mortels', 1, $duration);
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Gel: la cible ne peut pas attaquer pendant 1 tour
     */
    public function applyFreeze(array &$target, array &$logs, int $duration = 1): void
    {
        $effect = $this->createEffect('freeze', 'Gel', 1, $duration);
        $this->applyEffect($target, $effect, $logs);
    }

    /**
     * MÉTHODES DE VÉRIFICATION DES EFFETS NÉGATIFS
     */
    
    /**
     * Vérifie si un combattant est étourdi ou gelé (ne peut pas attaquer)
     */
    public function isStunnedOrFrozen(array &$fighter): bool
    {
        foreach ($fighter['statusEffects'] as $effect) {
            if ($effect['type'] === 'stun' || $effect['type'] === 'freeze') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Vérifie si un combattant est silencé (ne peut utiliser que le sort 1)
     */
    public function isSilenced(array &$fighter): bool
    {
        foreach ($fighter['statusEffects'] as $effect) {
            if ($effect['type'] === 'silence') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Vérifie si un combattant a ses passifs annulés
     */
    public function isNullified(array &$fighter): bool
    {
        foreach ($fighter['statusEffects'] as $effect) {
            if ($effect['type'] === 'nullify') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Vérifie si un combattant est bloqué (ne peut pas recevoir d'effets d'alliés)
     */
    public function isBlocked(array &$fighter): bool
    {
        foreach ($fighter['statusEffects'] as $effect) {
            if ($effect['type'] === 'blocker') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Vérifie si un combattant est provoqué et retourne l'ID du provoqueur
     */
    public function getTauntTarget(array &$fighter): ?int
    {
        foreach ($fighter['statusEffects'] as $effect) {
            if ($effect['type'] === 'taunt') {
                return $effect['value']; // ID du provoqueur
            }
        }
        return null;
    }
    
    /**
     * Vérifie si un combattant a l'effet Soins Mortels
     */
    public function hasHealReverse(array &$fighter): bool
    {
        foreach ($fighter['statusEffects'] as $effect) {
            if ($effect['type'] === 'heal_reverse') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Applique l'effet de soins inversés (transforme les soins en dégâts)
     */
    public function applyHealReverseEffect(array &$target, int $healAmount, array &$logs): int
    {
        if ($this->hasHealReverse($target)) {
            $damage = $healAmount;
            $target['hp'] -= $damage;
            
            $this->addCombatLog($logs, "Les soins se transforment en poison ! {$target['name']} (#{$target['id']}) subit {$damage} dégâts !");
            
            // Vérifier si le combattant meurt
            if ($target['hp'] <= 0) {
                $target['hp'] = 0;
                $target['isAlive'] = false;
                $this->addCombatLog($logs, "{$target['name']} (#{$target['id']}) est mort à cause des soins empoisonnés !");
            }
            
            return $damage; // Retourne les dégâts infligés au lieu des soins
        }
        
        return 0; // Aucun effet
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
                        
                    case 'damage_over_time':
                        $damagePercent = $effect['value'];
                        $damage = intval($fighter['maxHp'] * ($damagePercent / 100));
                        $fighter['hp'] -= $damage;
                        $this->addCombatLog($logs, "{$fighter['name']} (#{$fighter['id']}) subit {$damage} dégâts continus ({$damagePercent}% de ses PV max)");
                        break;
                        
                    // Les autres types d'effets sont traités à d'autres moments du combat
                    // (stun/freeze lors du choix d'action, silence lors du choix de skill, etc.)
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
                    // Pour les débuffs, restaurer les stats originales
                    elseif ($effect['type'] === 'debuff' && isset($effect['stat'])) {
                        $fighter[$effect['stat']] += $effect['value'];
                        $this->addCombatLog($logs, "L'effet {$effect['name']} s'estompe pour {$fighter['name']} (#{$fighter['id']})");
                    }
                    else {
                        // Pour les autres effets, juste annoncer la fin
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
     * MÉTHODES POUR LES DÉBUFFS DE STATISTIQUES
     */
    
    /**
     * Réduction Défense: -15% de défense
     */
    public function debuffDefense(array &$target, array &$logs, int $duration = 3, ?int $customValue = null): void
    {
        $reduction = $customValue ?? intval($target['defense'] * 0.15);
        $effect = $this->createEffect('debuff', 'Défense affaiblie', $reduction, $duration, 'defense');
        $this->applyEffect($target, $effect, $logs);
    }

    /**
     * Réduction Vitesse: -15% de vitesse
     */
    public function debuffSpeed(array &$target, array &$logs, int $duration = 3, ?int $customValue = null): void
    {
        $reduction = $customValue ?? intval($target['speed'] * 0.15);
        $effect = $this->createEffect('debuff', 'Ralentissement', $reduction, $duration, 'speed');
        $this->applyEffect($target, $effect, $logs);
    }

    /**
     * Réduction Attaque: -20% d'attaque
     */
    public function debuffAttack(array &$target, array &$logs, int $duration = 3, ?int $customValue = null): void
    {
        $reduction = $customValue ?? intval($target['attack'] * 0.20);
        $effect = $this->createEffect('debuff', 'Faiblesse', $reduction, $duration, 'attack');
        $this->applyEffect($target, $effect, $logs);
    }

    /**
     * Réduction Résistance: -20 de résistance
     */
    public function debuffResistance(array &$target, array &$logs, int $duration = 3, ?int $customValue = null): void
    {
        $reduction = $customValue ?? 20;
        $effect = $this->createEffect('debuff', 'Vulnérabilité', $reduction, $duration, 'resistance');
        $this->applyEffect($target, $effect, $logs);
    }
    
    /**
     * Applique un effet à un combattant en vérifiant les blocages
     */
     private function applyEffect(array &$target, array $effect, array &$logs): void
    {
        // Vérifier si la cible peut recevoir des effets (pas de blocker pour les effets bénéfiques d'alliés)
        if ($effect['type'] === 'buff' || $effect['type'] === 'shield' || $effect['type'] === 'protection') {
            if ($this->isBlocked($target)) {
                $this->addCombatLog($logs, "{$target['name']} (#{$target['id']}) ne peut pas recevoir l'effet {$effect['name']} (Bloqueur actif)");
                return;
            }
        }
        
        // Initialiser statusEffects si pas encore fait
        if (!isset($target['statusEffects'])) {
            $target['statusEffects'] = [];
        }
        
        // Ajouter l'effet
        $target['statusEffects'][] = $effect;
        
        // Pour les buffs, appliquer immédiatement l'effet
        if ($effect['type'] === 'buff' && isset($effect['stat'])) {
            $target[$effect['stat']] += $effect['value'];
        }
        // Pour les débuffs, réduire immédiatement l'effet
        elseif ($effect['type'] === 'debuff' && isset($effect['stat'])) {
            $target[$effect['stat']] -= $effect['value'];
            // S'assurer que les stats ne deviennent pas négatives
            if ($target[$effect['stat']] < 0) {
                $target[$effect['stat']] = 0;
            }
        }
        
        $this->addCombatLog($logs, "{$target['name']} (#{$target['id']}) reçoit l'effet {$effect['name']} !");
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
     */
    public function applyLifestealEffect(array &$attacker, int $damageDealt, array &$logs): void
    {
        foreach ($attacker['statusEffects'] as $effect) {
            if ($effect['type'] === 'lifesteal') {
                $healAmount = intval($damageDealt * ($effect['value'] / 100));
                
                if ($healAmount > 0) {
                    // Vérifier si l'attaquant a des soins inversés
                    if ($this->hasHealReverse($attacker)) {
                        $this->applyHealReverseEffect($attacker, $healAmount, $logs);
                    } else {
                        $attacker['hp'] = min($attacker['hp'] + $healAmount, $attacker['maxHp']);
                        $this->addCombatLog($logs, "{$attacker['name']} (#{$attacker['id']}) récupère {$healAmount} PV grâce à Soif de sang!");
                    }
                }
            }
        }
    }
    
    /**
     * Vérifie si le combattant doit contre-attaquer
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