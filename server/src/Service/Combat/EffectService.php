<?php

namespace App\Service\Combat;

use App\Entity\LinkSkillEffect;
use App\Entity\HeroSkill;
use App\Entity\SkillEffect;
use Doctrine\ORM\EntityManagerInterface;

class EffectService
{
    private const EFFECT_BUFF_ATTACK = 'buff_attack';
    private const EFFECT_BUFF_DEFENSE = 'buff_defense';
    private const EFFECT_BUFF_SPEED = 'buff_speed';
    private const EFFECT_BUFF_RESISTANCE = 'buff_resistance';
    private const EFFECT_BUFF_HP = 'buff_hp';
    
    private const EFFECT_DEBUFF_ATTACK = 'debuff_attack';
    private const EFFECT_DEBUFF_DEFENSE = 'debuff_defense';
    private const EFFECT_DEBUFF_SPEED = 'debuff_speed';
    private const EFFECT_DEBUFF_RESISTANCE = 'debuff_resistance';
    
    private const EFFECT_STUN = 'stun';
    private const EFFECT_SILENCE = 'silence';
    private const EFFECT_FREEZE = 'freeze';
    private const EFFECT_NULLIFY = 'nullify';
    private const EFFECT_BLOCKER = 'blocker';
    private const EFFECT_DOT = 'damage_over_time';
    private const EFFECT_HEAL_REVERSE = 'heal_reverse';
    
    private const EFFECT_SHIELD = 'shield';
    private const EFFECT_PROTECTION = 'protection';
    private const EFFECT_LIFESTEAL = 'lifesteal';
    private const EFFECT_COUNTER = 'counter';
    private const EFFECT_RESURRECTION = 'resurrection';
    private const EFFECT_TAUNT = 'taunt';
    
    // Mapping pour correspondre à la BDD
    private array $effectTypeMap = [
        'Augmentation Attaque' => self::EFFECT_BUFF_ATTACK,
        'Augmentation Défense' => self::EFFECT_BUFF_DEFENSE,
        'Augmentation Vitesse' => self::EFFECT_BUFF_SPEED,
        'Augmentation Résistance' => self::EFFECT_BUFF_RESISTANCE,
        'Augmentation PV' => self::EFFECT_BUFF_HP,
        
        'Réduction Attaque' => self::EFFECT_DEBUFF_ATTACK,
        'Réduction Défense' => self::EFFECT_DEBUFF_DEFENSE,
        'Réduction Vitesse' => self::EFFECT_DEBUFF_SPEED,
        'Réduction Résistance' => self::EFFECT_DEBUFF_RESISTANCE,
        
        'Etourdissement' => self::EFFECT_STUN,
        'Silence' => self::EFFECT_SILENCE,
        'Gel' => self::EFFECT_FREEZE,
        'Annulation' => self::EFFECT_NULLIFY,
        'Bloqueur' => self::EFFECT_BLOCKER,
        'Dégâts continue' => self::EFFECT_DOT,
        'Soins Mortel' => self::EFFECT_HEAL_REVERSE,
        
        'Bouclier' => self::EFFECT_SHIELD,
        'Protection' => self::EFFECT_PROTECTION,
        'Soif de sang' => self::EFFECT_LIFESTEAL,
        'Contre' => self::EFFECT_COUNTER,
        'Sauvetage' => self::EFFECT_RESURRECTION,
        'Provocation' => self::EFFECT_TAUNT,
    ];

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    /**
     * Obtient le type d'effet à partir du nom de l'effet
     */
    private function getEffectTypeFromName(string $effectName): string
    {
        return $this->effectTypeMap[$effectName] ?? 'unknown';
    }

    /**
     * Applique les effets d'un skill depuis la BDD
     */
    public function applySkillEffects($skill, array &$attacker, array &$target, array &$logs, array &$allFighters): void
    {
        // Récupérer les liens effet-skill depuis la BDD
        $linkRepository = $this->entityManager->getRepository(LinkSkillEffect::class);
        $effectLinks = $linkRepository->findBy(['skill' => $skill]);
        
        foreach ($effectLinks as $link) {
            // Vérifier la chance d'application
            $accuracy = $link->getAccuracy();
            if (rand(1, 100) > $accuracy) {
                $this->addCombatLog($logs, "L'effet {$link->getEffect()->getName()} de {$skill->getName()} ne se déclenche pas ({$accuracy}% de chance)");
                continue; // L'effet ne se déclenche pas
            }
            
            // Déterminer le type d'effet
            $effectName = $link->getEffect()->getName();
            $effectType = $this->getEffectTypeFromName($effectName);
            
            // Déterminer la cible selon le type d'effet
            $effectTarget = null;
            
            // Par défaut, déterminer la cible en fonction du type d'effet
            if ($this->isBuffEffect($effectType)) {
                // Les buffs vont généralement sur l'attaquant ou ses alliés
                $effectTarget = &$attacker;
            } else if ($this->isDebuffEffect($effectType)) {
                // Les debuffs vont généralement sur la cible
                $effectTarget = &$target;
            } else if ($effectType === self::EFFECT_SHIELD || 
                       $effectType === self::EFFECT_PROTECTION || 
                       $effectType === self::EFFECT_LIFESTEAL ||
                       $effectType === self::EFFECT_COUNTER ||
                       $effectType === self::EFFECT_RESURRECTION) {
                // Effets positifs spéciaux vont sur l'attaquant ou ses alliés
                $effectTarget = &$attacker;
            } else {
                // Autres effets (stun, silence, etc.) vont sur la cible
                $effectTarget = &$target;
            }
            
            // Appliquer l'effet
            $value = $link->getValue();
            $duration = $link->getDuration();
            
            $this->addCombatLog($logs, "Application de l'effet {$effectName} avec valeur {$value} pour {$duration} tours");
            
            $this->applyEffectByType(
                $effectType,
                $effectTarget,
                $logs,
                $attacker,
                $value,
                $duration,
                null // Scale on n'est plus utilisé
            );
        }
    }

    /**
     * Détermine si un type d'effet est un buff
     */
    private function isBuffEffect(string $effectType): bool
    {
        return in_array($effectType, [
            self::EFFECT_BUFF_ATTACK,
            self::EFFECT_BUFF_DEFENSE,
            self::EFFECT_BUFF_HP,
            self::EFFECT_BUFF_RESISTANCE,
            self::EFFECT_BUFF_SPEED
        ]);
    }
    
    /**
     * Détermine si un type d'effet est un debuff
     */
    private function isDebuffEffect(string $effectType): bool
    {
        return in_array($effectType, [
            self::EFFECT_DEBUFF_ATTACK,
            self::EFFECT_DEBUFF_DEFENSE,
            self::EFFECT_DEBUFF_RESISTANCE,
            self::EFFECT_DEBUFF_SPEED
        ]);
    }

    public function applyEffectByType(string $effectType, array &$target, array &$logs, array &$caster = null, $value = null, int $duration = 1, ?string $scaleOn = null): void
    {
        switch ($effectType) {
            // BUFFS DE STATISTIQUES
            case self::EFFECT_BUFF_HP:
                $this->buffHp($target, $logs, $duration, $value);
                break;
            case self::EFFECT_BUFF_DEFENSE:
                $this->buffDefense($target, $logs, $duration, $value);
                break;
            case self::EFFECT_BUFF_ATTACK:
                $this->buffAttack($target, $logs, $duration, $value);
                break;
            case self::EFFECT_BUFF_SPEED:
                $this->buffSpeed($target, $logs, $duration, $value);
                break;
            case self::EFFECT_BUFF_RESISTANCE:
                $this->buffResistance($target, $logs, $duration, $value);
                break;
                
            // DÉBUFFS DE STATISTIQUES
            case self::EFFECT_DEBUFF_DEFENSE:
                $this->debuffDefense($target, $logs, $duration, $value);
                break;
            case self::EFFECT_DEBUFF_SPEED:
                $this->debuffSpeed($target, $logs, $duration, $value);
                break;
            case self::EFFECT_DEBUFF_ATTACK:
                $this->debuffAttack($target, $logs, $duration, $value);
                break;
            case self::EFFECT_DEBUFF_RESISTANCE:
                $this->debuffResistance($target, $logs, $duration, $value);
                break;
                
            // EFFETS SPÉCIAUX POSITIFS
            case self::EFFECT_SHIELD:
                if ($caster) {
                    $this->applyShield($target, $caster, $logs, $duration, $value);
                }
                break;
            case self::EFFECT_PROTECTION:
                if ($caster) {
                    $this->applyProtection($target, $caster, $logs, $duration);
                }
                break;
            case self::EFFECT_LIFESTEAL:
                $this->applyLifesteal($target, $logs, $duration, $value);
                break;
            case self::EFFECT_COUNTER:
                $this->applyCounter($target, $logs, $duration, $value);
                break;
            case self::EFFECT_RESURRECTION:
                $this->applyResurrection($target, $logs, $duration, $value);
                break;
                
            // EFFETS NÉGATIFS
            case self::EFFECT_STUN:
                $this->applyStun($target, $logs, $duration);
                break;
            case self::EFFECT_SILENCE:
                $this->applySilence($target, $logs, $duration);
                break;
            case self::EFFECT_NULLIFY:
                $this->applyNullify($target, $logs, $duration);
                break;
            case self::EFFECT_BLOCKER:
                $this->applyBlocker($target, $logs, $duration);
                break;
            case self::EFFECT_DOT:
                $this->applyDamageOverTime($target, $logs, $duration, $value);
                break;
            case self::EFFECT_TAUNT:
                if ($caster) {
                    $this->applyTaunt($target, $caster, $logs, $duration);
                }
                break;
            case self::EFFECT_HEAL_REVERSE:
                $this->applyHealReverse($target, $logs, $duration);
                break;
            case self::EFFECT_FREEZE:
                $this->applyFreeze($target, $logs, $duration);
                break;
            default:
                $this->addCombatLog($logs, "Effet inconnu : {$effectType}");
                break;
        }
    }

    // Le reste des méthodes reste inchangé car elles ne dépendent pas directement de la structure des entités
    
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

    /**
     * Vérifie si un sort ignore la défense
     * @param HeroSkill $skill Le sort à vérifier
     * @return bool True si le sort ignore la défense, false sinon
     */
    public function skillIgnoresDefense(HeroSkill $skill): bool
    {
        // Récupérer les liens effet-skill depuis la BDD
        $linkRepository = $this->entityManager->getRepository(LinkSkillEffect::class);
        $effectLinks = $linkRepository->findBy(['skill' => $skill]);
        
        foreach ($effectLinks as $link) {
            if ($link->getEffect()->getName() === 'Ignore la défense') {
                return true;
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