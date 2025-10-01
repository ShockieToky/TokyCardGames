<?php
// filepath: c:\Users\AYMERICK\Documents\GitHub\TokyCardGames\server\src\Service\Combat\AttackRulesService.php

namespace App\Service\Combat;

use App\Entity\Hero;
use App\Entity\HeroSkill;
use App\Entity\SkillEffect;
use App\Repository\HeroSkillRepository;
use App\Repository\SkillEffectRepository;
use Doctrine\ORM\EntityManagerInterface;


class AttackRulesService
{
    private EffectService $effectService;
    private SkillEffectRepository $skillEffectRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(EffectService $effectService, SkillEffectRepository $skillEffectRepository, EntityManagerInterface $entityManager) 
    {
        $this->effectService = $effectService;
        $this->skillEffectRepository = $skillEffectRepository;
        $this->entityManager = $entityManager;
    }
    /**
     * Sélectionne une cible pour l'attaque en fonction du type de ciblage
     */
    public function selectTarget(array $attacker, array $fighters, ?string $targeting = null, ?string $targetingTeam = 'enemy'): ?array
    {
        // Si ciblage personnalisé spécifié (pour les compétences)
        if ($targeting) {
            $targeting = json_decode($targeting, true) ?: [];
            return $this->selectCustomTarget($attacker, $fighters, $targeting, $targetingTeam);
        }
        
        // Ciblage par défaut (aléatoire dans l'équipe ennemie)
        $targetTeam = $targetingTeam === 'ally' ? $attacker['team'] : ($attacker['team'] === 'A' ? 'B' : 'A');
        
        // Filtrer les cibles potentielles
        $possibleTargets = array_filter($fighters, function($fighter) use ($targetTeam) {
            return $fighter['team'] === $targetTeam && ($fighter['isAlive'] ?? false);
        });
        
        if (empty($possibleTargets)) {
            return null;
        }
        
        // Retourner une cible aléatoire
        return $possibleTargets[array_rand($possibleTargets)];
    }
    
    /**
     * Sélectionne une cible selon des critères personnalisés
     */
    private function selectCustomTarget(array $attacker, array $fighters, array $targeting, string $targetingTeam): ?array
    {
        $targetTeam = $targetingTeam === 'ally' ? $attacker['team'] : ($attacker['team'] === 'A' ? 'B' : 'A');
        
        // Filtrer par équipe vivante
        $targets = array_filter($fighters, function($fighter) use ($targetTeam) {
            return $fighter['team'] === $targetTeam && ($fighter['isAlive'] ?? false);
        });
        
        if (empty($targets)) {
            return null;
        }
        
        // Appliquer les critères de ciblage
        if (isset($targeting['type'])) {
            switch ($targeting['type']) {
                case 'lowest_hp':
                    usort($targets, function($a, $b) {
                        return $a['hp'] <=> $b['hp'];
                    });
                    return $targets[0];
                
                case 'highest_hp':
                    usort($targets, function($a, $b) {
                        return $b['hp'] <=> $a['hp'];
                    });
                    return $targets[0];
                
                case 'lowest_defense':
                    usort($targets, function($a, $b) {
                        return $a['defense'] <=> $b['defense'];
                    });
                    return $targets[0];
                
                case 'highest_attack':
                    usort($targets, function($a, $b) {
                        return $b['attack'] <=> $a['attack'];
                    });
                    return $targets[0];
                
                case 'aoe':
                    // Pour AOE, on retourne toutes les cibles possibles
                    return ['aoe' => true, 'targets' => $targets];
                
                case 'random':
                default:
                    return $targets[array_rand($targets)];
            }
        }
        
        // Par défaut, cible aléatoire
        return $targets[array_rand($targets)];
    }

    private function doesSkillIgnoreDefense(HeroSkill $skill): bool
    {
        // Récupérer les liens effet-skill depuis la BDD
        $linkRepository = $this->entityManager->getRepository(\App\Entity\LinkSkillEffect::class);
        $effectLinks = $linkRepository->findBy(['skill' => $skill]);
        
        foreach ($effectLinks as $link) {
            if ($link->getEffect()->getName() === 'Ignore la défense') {
                return true;
            }
        }
        
        // Vérifier aussi dans le scaling (pour la compatibilité avec l'ancien système)
        $scaling = json_decode($skill->getScaling(), true) ?: [];
        return isset($scaling['ignore_defense']) && $scaling['ignore_defense'];
    }
    
    /**
     * Exécute une compétence sur une cible
     */
    public function executeSkill(array &$attacker, HeroSkill $skill, array &$target, array &$fighters, array &$logs): array
    {
        // Vérifier si la compétence est en cooldown
        if ($attacker['cooldowns'][$skill->getId()] ?? 0 > 0) {
            $this->addCombatLog($logs, "{$attacker['name']} ne peut pas utiliser {$skill->getName()} (encore en cooldown)");
            return ['success' => false];
        }
        
        // Appliquer le cooldown
        $attacker['cooldowns'][$skill->getId()] = $skill->getCooldown();
        
        $this->addCombatLog($logs, "{$attacker['name']} utilise {$skill->getName()}!");
        
        $totalDamage = 0;
        $hitCount = 0;
        
        // Récupérer les effets de la compétence
        $skillEffects = $this->skillEffectRepository->findBy(['skill' => $skill]);
        
        // Si la compétence fait des dégâts
        if ($skill->getDoesDamage()) {
            // Gérer les attaques à plusieurs coups
            for ($i = 0; $i < $skill->getHitsNumber(); $i++) {
                // Calculer les dégâts
                $damage = $this->calculateSkillDamage($attacker, $target, $skill);
                
                // Appliquer les dégâts
                $appliedDamage = $this->applyDamage($attacker, $target, $damage, $logs);
                $totalDamage += $appliedDamage;
                $hitCount++;
                
                $this->addCombatLog($logs, "Le coup #" . ($i+1) . " inflige $appliedDamage dégâts à {$target['name']}");
                
                // Si la cible est morte, arrêter les coups suivants
                if (!$target['isAlive']) break;
            }
        }
        
        // Appliquer les effets supplémentaires de la compétence
        $this->applySkillEffects($attacker, $target, $fighters, $skill, $skillEffects, $logs);
        
        // Vérifier les effets de contre-attaque
        if ($totalDamage > 0) {
            $this->checkCounterEffects($target, $attacker, $logs);
            
            // Appliquer les effets de vol de vie
            $this->applyLifestealEffects($attacker, $totalDamage, $logs);
        }
        
        return [
            'success' => true,
            'damage' => $totalDamage,
            'hits' => $hitCount,
        ];
    }
    
    /**
     * Calcule les dégâts d'une compétence
     */
    private function calculateSkillDamage(array $attacker, array $target, HeroSkill $skill): int
    {
        // Récupérer le scaling de la compétence
        $scaling = json_decode($skill->getScaling(), true) ?: [];
        
        // Base damage
        $damage = $attacker['attack'];
        
        // Appliquer le multiplicateur de la compétence
        $damage *= $skill->getMultiplicator();
        
        // Appliquer le scaling sur les stats
        foreach ($scaling as $stat => $value) {
            if ($stat === 'ignore_defense') continue; // Ignorer cette clé, traitée séparément
            
            switch ($stat) {
                case 'attack':
                    $damage += $attacker['attack'] * $value;
                    break;
                case 'hp':
                    $damage += $attacker['maxHp'] * $value;
                    break;
                case 'defense':
                    $damage += $attacker['defense'] * $value;
                    break;
                case 'speed':
                    $damage += $attacker['speed'] * $value;
                    break;
                case 'target_defense':
                    $damage -= $target['defense'] * $value;
                    break;
            }
        }
        
        // Vérifier si la compétence ignore la défense
        $ignoreDefense = $this->doesSkillIgnoreDefense($skill);
        
        // Prise en compte de la défense (sauf si le sort l'ignore)
        if (!$ignoreDefense) {
            $defenseReduction = $target['defense'] / 2;
            $damage = max(1, $damage - $defenseReduction);
        }
        
        // Arrondir à l'entier
        return max(1, (int)round($damage));
    }
    
    /**
     * Applique les dégâts à une cible
     */
    private function applyDamage(array &$attacker, array &$target, int $damage, array &$logs): int
    {
        // Vérifier si la cible a un bouclier
        if ($this->effectService) {
            $damage = $this->effectService->applyShieldEffect($target, $damage, $logs);
            
            // Vérifier si la cible a un protecteur
            $protector = $this->effectService->findProtector($target, $logs);
            if ($protector) {
                $this->addCombatLog($logs, "{$protector['name']} protège {$target['name']} et reçoit $damage dégâts!");
                $target = $protector; // Rediriger les dégâts vers le protecteur
            }
        }
        
        // Appliquer les dégâts
        $target['hp'] -= $damage;
        
        // Vérifier si la cible est morte
        if ($target['hp'] <= 0) {
            $target['hp'] = 0;
            $target['isAlive'] = false;
            $this->addCombatLog($logs, "{$target['name']} (#{$target['id']}) est mort !");
            
            // Vérifier les effets de résurrection si l'EffectService est disponible
            if ($this->effectService) {
                $this->effectService->checkForResurrection($target, $logs);
            }
        }
        
        return $damage;
    }
    
    /**
     * Applique les effets d'une compétence
     */
    private function applySkillEffects(array &$attacker, array &$target, array &$fighters, HeroSkill $skill, array $skillEffects, array &$logs): void
    {
        if (!$this->effectService) return;
        
        foreach ($skillEffects as $effect) {
            // Vérifier la chance d'application (en utilisant précision vs résistance)
            $chance = $this->calculateEffectChance($attacker, $target, $effect);
            if (rand(1, 100) > $chance) {
                $this->addCombatLog($logs, "L'effet {$effect->getEffectType()} n'a pas été appliqué (chance: $chance%)");
                continue;
            }
            
            // Déterminer la ou les cibles de l'effet
            $effectTargets = $this->getEffectTargets($attacker, $target, $fighters, $effect->getTargetSide());
            
            // Pour chaque cible, appliquer l'effet
            foreach ($effectTargets as &$effectTarget) {
                $value = $this->calculateEffectValue($attacker, $effectTarget, $effect);
                
                switch ($effect->getEffectType()) {
                    case 'buff_hp':
                        $this->effectService->buffHp($effectTarget, $logs, $effect->getDuration(), $value);
                        break;
                    case 'buff_defense':
                        $this->effectService->buffDefense($effectTarget, $logs, $effect->getDuration(), $value);
                        break;
                    case 'buff_attack':
                        $this->effectService->buffAttack($effectTarget, $logs, $effect->getDuration(), $value);
                        break;
                    case 'buff_speed':
                        $this->effectService->buffSpeed($effectTarget, $logs, $effect->getDuration(), $value);
                        break;
                    case 'buff_resistance':
                        $this->effectService->buffResistance($effectTarget, $logs, $effect->getDuration(), $value);
                        break;
                    case 'shield':
                        $this->effectService->applyShield($effectTarget, $attacker, $logs, $effect->getDuration(), $value);
                        break;
                    case 'protection':
                        $this->effectService->applyProtection($effectTarget, $attacker, $logs, $effect->getDuration());
                        break;
                    case 'lifesteal':
                        $this->effectService->applyLifesteal($effectTarget, $logs, $effect->getDuration(), $value);
                        break;
                    case 'counter':
                        $this->effectService->applyCounter($effectTarget, $logs, $effect->getDuration(), $value);
                        break;
                    case 'resurrection':
                        $this->effectService->applyResurrection($effectTarget, $logs, $effect->getDuration(), $value);
                        break;
                    case 'poison':
                        $this->effectService->applyPoison($effectTarget, $logs, $value, $effect->getDuration());
                        break;
                    case 'regeneration':
                        $this->effectService->applyRegeneration($effectTarget, $logs, $value, $effect->getDuration());
                        break;
                    case 'stun':
                        // Nouvel effet pour l'étourdissement
                        $this->effectService->applyStun($effectTarget, $logs, $effect->getDuration());
                        break;
                }
            }
        }
    }
    
    /**
     * Calcule la chance d'application d'un effet (précision vs résistance)
     */
    private function calculateEffectChance(array $attacker, array $target, SkillEffect $effect): int
    {
        $baseChance = $effect->getChance();
        
        // Si c'est un effet de buff positif sur un allié, pas besoin de vérifier la résistance
        if (strpos($effect->getEffectType(), 'buff_') === 0 && $effect->getTargetSide() === 'ally') {
            return $baseChance;
        }
        
        // Si c'est un effet négatif ou de contrôle
        $accuracy = $attacker['accuracy'] ?? 0;
        $resistance = $target['resistance'] ?? 0;
        
        // Formule: chance de base + précision - résistance
        $effectiveChance = $baseChance + $accuracy - $resistance;
        
        // Limiter entre 15% (minimum) et 85% (maximum)
        return max(15, min(85, $effectiveChance));
    }
    
    /**
     * Calcule la valeur d'un effet en fonction du scaling
     */
    private function calculateEffectValue(array $attacker, array $target, SkillEffect $effect): int
    {
        $baseValue = $effect->getValue();
        $scaleOn = json_decode($effect->getScaleOn(), true) ?: [];
        
        // Si aucun scaling, retourner la valeur de base
        if (empty($scaleOn)) {
            return (int)round($baseValue);
        }
        
        // Appliquer le scaling
        foreach ($scaleOn as $stat => $multiplier) {
            switch ($stat) {
                case 'caster_attack':
                    $baseValue += $attacker['attack'] * $multiplier;
                    break;
                case 'caster_hp':
                    $baseValue += $attacker['maxHp'] * $multiplier;
                    break;
                case 'caster_defense':
                    $baseValue += $attacker['defense'] * $multiplier;
                    break;
                case 'target_hp':
                    $baseValue += $target['maxHp'] * $multiplier;
                    break;
            }
        }
        
        return (int)round($baseValue);
    }
    
    /**
     * Détermine les cibles d'un effet
     */
    private function getEffectTargets(array $attacker, array $target, array $fighters, string $targetSide): array
    {
        // Cas de l'effet ciblé sur un seul combattant
        if (is_array($target) && !isset($target['aoe'])) {
            if ($targetSide === 'self') {
                return [$attacker];
            } elseif ($targetSide === 'ally' && $target['team'] === $attacker['team']) {
                return [$target];
            } elseif ($targetSide === 'enemy' && $target['team'] !== $attacker['team']) {
                return [$target];
            }
        }
        
        // Cas d'une AOE ou d'une cible multiple
        $targets = [];
        
        switch ($targetSide) {
            case 'self':
                $targets[] = $attacker;
                break;
                
            case 'ally':
                // Cibler tous les alliés vivants
                foreach ($fighters as &$fighter) {
                    if ($fighter['team'] === $attacker['team'] && $fighter['isAlive']) {
                        $targets[] = $fighter;
                    }
                }
                break;
                
            case 'enemy':
                // Cibler tous les ennemis vivants
                foreach ($fighters as &$fighter) {
                    if ($fighter['team'] !== $attacker['team'] && $fighter['isAlive']) {
                        $targets[] = $fighter;
                    }
                }
                break;
                
            case 'all':
                // Cibler tous les combattants vivants
                foreach ($fighters as &$fighter) {
                    if ($fighter['isAlive']) {
                        $targets[] = $fighter;
                    }
                }
                break;
        }
        
        return $targets;
    }
    
    /**
     * Vérifie si la cible doit contre-attaquer
     */
    private function checkCounterEffects(array &$target, array &$attacker, array &$logs): void
    {
        if ($this->effectService && $target['isAlive']) {
            if ($this->effectService->shouldCounterAttack($target, $logs)) {
                // Au lieu d'une attaque basique, utiliser la première compétence
                $this->addCombatLog($logs, "{$target['name']} contre-attaque!");
                
                // Trouver le premier skill du personnage
                $firstSkill = $this->entityManager->getRepository(HeroSkill::class)
                    ->findOneBy(['hero' => $target['heroId']], ['id' => 'ASC']);
                
                if ($firstSkill) {
                    // Exécuter le skill comme contre-attaque
                    $this->executeSkill($target, $firstSkill, $attacker, [$attacker, $target], $logs);
                } else {
                    // Fallback: appliquer des dégâts directs si aucun skill n'est trouvé
                    $damage = (int)($target['attack'] * 0.7);
                    $this->applyDamage($target, $attacker, $damage, $logs);
                    $this->addCombatLog($logs, "{$target['name']} contre-attaque et inflige {$damage} dégâts!");
                }
            }
        }
    }
    
    /**
     * Applique les effets de vol de vie
     */
    private function applyLifestealEffects(array &$attacker, int $damage, array &$logs): void
    {
        if ($this->effectService) {
            $this->effectService->applyLifestealEffect($attacker, $damage, $logs);
        }
    }
    
    /**
     * Ajoute un log d'action
     */
    private function addCombatLog(array &$logs, string $message): void
    {
        $logs[] = [
            'timestamp' => time(),
            'message' => $message
        ];
    }
}