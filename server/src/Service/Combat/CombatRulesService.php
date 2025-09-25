<?php
// filepath: c:\Users\AYMERICK\Documents\GitHub\TokyCardGames\server\src\Service\Combat\CombatRulesService.php
namespace App\Service\Combat;

use App\Entity\Hero;

class CombatRulesService
{
    private AttackService $attackService;
    private EffectService $effectService;
    
    public function __construct(AttackService $attackService, EffectService $effectService)
    {
        $this->attackService = $attackService;
        $this->effectService = $effectService;
    }
    /**
     * Convertit un objet Hero en combattant pour le système de combat.
     */
    public function convertHeroToFighter(Hero $hero, int $fighterId): array
    {
        return [
            'id' => $fighterId,
            'heroId' => $hero->getId(),
            'name' => $hero->getName(),
            'hp' => $hero->getHP(),
            'maxHp' => $hero->getHP(),
            'attack' => $hero->getATK(),
            'defense' => $hero->getDEF(),
            'speed' => $hero->getVIT(),
            'resistance' => $hero->getRES(),
            'star' => $hero->getStar(),
            'type' => $hero->getType(),
            'isAlive' => true,
            'team' => '',
            'statusEffects' => [],
            'cooldowns' => []
        ];
    }

    /**
     * Initialise un combat avec les équipes données.
     * @param Hero[] $teamAHeroes
     * @param Hero[] $teamBHeroes
     */
    public function initializeCombat(array $teamAHeroes, array $teamBHeroes): array
    {
        $fighters = [];
        $fighterId = 1;
        
        // Conversion des héros en combattants
        foreach ($teamAHeroes as $hero) {
            $fighter = $this->convertHeroToFighter($hero, $fighterId++);
            $fighter['team'] = 'A';
            $fighters[] = $fighter;
        }
        
        foreach ($teamBHeroes as $hero) {
            $fighter = $this->convertHeroToFighter($hero, $fighterId++);
            $fighter['team'] = 'B';
            $fighters[] = $fighter;
        }
        
        // Initialisation de l'état de combat
        $combatState = [
            'fighters' => $fighters,
            'turn' => 1,
            'hasPlayed' => [],
            'currentTurnOrder' => [],
            'logs' => [],
            'round' => 1,
            'phase' => 'start',
            'winner' => null,
        ];
        
        // Initialiser la liste des héros ayant joué
        foreach ($fighters as $f) {
            $combatState['hasPlayed'][$f['id']] = false;
        }
        
        // Calculer l'ordre d'attaque initial
        $combatState['currentTurnOrder'] = $this->getAttackOrder($combatState['fighters']);
        
        return $combatState;
    }

    /**
     * Détermine l'ordre d'attaque pour le tour en cours.
     */
    public function getAttackOrder(array $fighters): array
    {
        // On trie les héros vivants par vitesse décroissante
        $alive = array_filter($fighters, function($f) {
            return !empty($f['isAlive']);
        });

        // Pour garantir l'aléatoire sur les égalités, on mélange d'abord
        $alive = array_values($alive); // réindexation
        shuffle($alive);

        // Puis on trie par vitesse décroissante
        usort($alive, function($a, $b) {
            return $b['speed'] <=> $a['speed'];
        });

        return array_map(function($f) { return $f['id']; }, $alive);
    }

    /**
     * Exécute un tour de jeu pour un seul combattant.
     */
    public function executeTurn(array &$combatState): void
    {
        // Réinitialiser la liste des héros ayant joué si tous ont joué
        if ($this->allFightersPlayed($combatState)) {
            $combatState['turn']++;
            foreach ($combatState['fighters'] as &$f) {
                if ($f['isAlive']) {
                    $combatState['hasPlayed'][$f['id']] = false;
                }
            }
            
            // Recalculer l'ordre d'attaque
            $combatState['currentTurnOrder'] = $this->getAttackOrder($combatState['fighters']);
        }

        // Trouver le prochain héros à jouer
        foreach ($combatState['currentTurnOrder'] as $id) {
            if (empty($combatState['hasPlayed'][$id])) {
                // Trouver le héros qui joue
                $attacker = &$this->findFighterById($combatState['fighters'], $id);
                if (!$attacker || !$attacker['isAlive']) {
                    $combatState['hasPlayed'][$id] = true;
                    continue;
                }

                // Sélectionner une cible
                $targetIndex = null;
                $target = $this->attackService->selectTarget($attacker, $combatState['fighters']);
                
                if ($target === null) {
                    $combatState['hasPlayed'][$id] = true;
                    break;
                }
                
                // Trouver l'index de la cible
                foreach ($combatState['fighters'] as $index => $fighter) {
                    if ($fighter['id'] === $target['id']) {
                        $targetIndex = $index;
                        break;
                    }
                }

                // Exécuter l'attaque
                $attackResult = $this->attackService->executeBasicAttack(
                    $attacker, 
                    $combatState['fighters'][$targetIndex], 
                    $combatState['logs']
                );
                
                $combatState['hasPlayed'][$id] = true;
                break; // Un seul héros joue par appel
            }
        }
    }

    /**
     * Trouve un combattant par son ID (retourne une référence).
     */
    private function &findFighterById(array &$fighters, int $id): ?array
    {
        foreach ($fighters as &$fighter) {
            if ($fighter['id'] === $id) {
                return $fighter;
            }
        }
        $null = null;
        return $null;
    }

    /**
     * Vérifie si tous les héros vivants ont joué ce tour de jeu.
     */
    private function allFightersPlayed(array $combatState): bool
    {
        foreach ($combatState['currentTurnOrder'] as $id) {
            if (empty($combatState['hasPlayed'][$id])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Gestion des cooldowns pour tous les combattants.
     */
    public function manageCooldowns(array &$fighters): void
    {
        foreach ($fighters as &$fighter) {
            if (!$fighter['isAlive']) continue;
            
            foreach ($fighter['cooldowns'] as $skillId => &$cooldown) {
                if ($cooldown > 0) {
                    $cooldown--;
                }
            }
        }
    }

    /**
     * Gère un round complet de combat.
     */
    public function executeRound(array &$combatState): void
    {
        // Phase de début de tour
        $this->addCombatLog($combatState['logs'], "Début du tour {$combatState['round']}");
        
        // Traiter les effets actifs
        $this->effectService->processEffects($combatState['fighters'], $combatState['logs']);
        
        // Phase principale: chaque combattant joue dans l'ordre
        while (!$this->allFightersPlayed($combatState) && !$this->isCombatFinished($combatState['fighters'])) {
            $this->executeTurn($combatState);
        }
        
        // Phase de fin de tour
        $this->manageCooldowns($combatState['fighters']);
        
        // Vérifier si le combat est terminé
        if ($this->isCombatFinished($combatState['fighters'])) {
            $combatState['winner'] = $this->determineWinner($combatState['fighters']);
            $this->addCombatLog($combatState['logs'], 
                "Combat terminé ! L'équipe {$combatState['winner']} gagne !");
        } else {
            $this->addCombatLog($combatState['logs'], "Fin du tour {$combatState['round']}");
            $combatState['round']++;
        }
    }

    /**
     * Exécute un combat complet et retourne le résultat.
     */
    public function runFullCombat(array $teamAHeroes, array $teamBHeroes, int $maxRounds = 30): array
    {
        $combatState = $this->initializeCombat($teamAHeroes, $teamBHeroes);
        
        while (!$this->isCombatFinished($combatState['fighters']) && $combatState['round'] <= $maxRounds) {
            $this->executeRound($combatState);
        }
        
        // Si le combat atteint le nombre maximum de tours
        if ($combatState['round'] > $maxRounds && !$combatState['winner']) {
            $combatState['winner'] = 'draw';
            $this->addCombatLog($combatState['logs'], 
                "Le combat se termine en match nul (nombre maximum de tours atteint) !");
        }
        
        return $combatState;
    }

    /**
     * Vérifie si le combat est terminé.
     */
    public function isCombatFinished(array $fighters): bool
    {
        $teamsAlive = [];
        foreach ($fighters as $f) {
            if (!empty($f['isAlive'])) {
                $teamsAlive[$f['team']] = true;
            }
        }
        return count($teamsAlive) <= 1;
    }

    /**
     * Détermine l'équipe gagnante.
     */
    public function determineWinner(array $fighters): ?string
    {
        foreach ($fighters as $f) {
            if (!empty($f['isAlive'])) {
                return $f['team'];
            }
        }
        return null; // Match nul (tous morts)
    }

    /**
     * Ajoute un log d'action au combat.
     */
    public function addCombatLog(array &$logs, string $message): void
    {
        $logs[] = [
            'timestamp' => time(),
            'message' => $message
        ];
    }

    /**
     * Récupère tous les logs du combat.
     */
    public function getCombatLogs(array $logs): array
    {
        return $logs;
    }
}