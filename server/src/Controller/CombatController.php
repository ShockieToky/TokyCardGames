<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\Combat\CombatRulesService;
use App\Repository\HeroSkillRepository;
use App\Repository\SkillEffectRepository;
use App\Service\Combat\EffectService;

class CombatController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CombatRulesService $combatRulesService;
    private HeroSkillRepository $heroSkillRepository;
    private SkillEffectRepository $skillEffectRepository;
    private EffectService $effectService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CombatRulesService $combatRulesService,
        HeroSkillRepository $heroSkillRepository,
        SkillEffectRepository $skillEffectRepository,
        EffectService $effectService
    ) {
        $this->entityManager = $entityManager;
        $this->combatRulesService = $combatRulesService;
        $this->heroSkillRepository = $heroSkillRepository;
        $this->skillEffectRepository = $skillEffectRepository;
        $this->effectService = $effectService;
    }

    #[Route('/combat/start', name: 'combat_start', methods: ['POST', 'OPTIONS'])]
    public function startCombat(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['teamA']) || !isset($data['teamB'])) {
                return new JsonResponse(['error' => 'Teams are required'], Response::HTTP_BAD_REQUEST);
            }

            // Récupérer les héros des équipes
            $heroRepository = $this->entityManager->getRepository(\App\Entity\Hero::class);
            
            $teamAHeroes = [];
            foreach ($data['teamA'] as $heroId) {
                $hero = $heroRepository->find($heroId);
                if ($hero) {
                    $teamAHeroes[] = $hero;
                }
            }
            
            $teamBHeroes = [];
            foreach ($data['teamB'] as $heroId) {
                $hero = $heroRepository->find($heroId);
                if ($hero) {
                    $teamBHeroes[] = $hero;
                }
            }

            if (count($teamAHeroes) !== 4 || count($teamBHeroes) !== 4) {
                return new JsonResponse(['error' => 'Each team must have exactly 4 heroes'], Response::HTTP_BAD_REQUEST);
            }

            // Initialiser le combat avec le service
            $combatState = $this->combatRulesService->initializeCombat($teamAHeroes, $teamBHeroes);
            
            // Créer un ID de combat unique
            $combatId = uniqid('combat_');
            
            // Construire l'état de combat complet
            $fullCombatState = [
                'id' => $combatId,
                'fighters' => $combatState['fighters'] ?? [],
                'currentTurnOrder' => $combatState['currentTurnOrder'] ?? [],
                'hasPlayed' => [],
                'turn' => 1,
                'phase' => 'battle',
                'logs' => [
                    ['message' => 'Le combat commence !', 'timestamp' => time()]
                ],
                'winner' => null,
                'heroMapping' => [], // Pour stocker la correspondance fighter -> hero
                'skillCooldowns' => [] // Pour gérer les cooldowns des skills par fighter
            ];

            // Créer la correspondance fighters -> héros et initialiser les cooldowns
            foreach ($fullCombatState['fighters'] as $fighter) {
                $fighter['statusEffects'] = []; // Initialiser les effets de statut
                foreach (array_merge($teamAHeroes, $teamBHeroes) as $hero) {
                    if ($hero->getId() == $fighter['heroId']) {
                        $fullCombatState['heroMapping'][$fighter['id']] = $hero;
                        
                        // Initialiser les cooldowns des skills du fighter
                        $heroSkills = $this->heroSkillRepository->findBy(['hero' => $hero]);
                        $fullCombatState['skillCooldowns'][$fighter['id']] = [];
                        
                        foreach ($heroSkills as $skill) {
                            // Utiliser initial_cooldown au début du combat
                            $fullCombatState['skillCooldowns'][$fighter['id']][$skill->getId()] = $skill->getInitialCooldown();
                        }
                        break;
                    }
                }
            }

            // Initialiser hasPlayed pour tous les fighters
            foreach ($fullCombatState['fighters'] as $fighter) {
                $fullCombatState['hasPlayed'][$fighter['id']] = false;
            }
            
            // Stocker l'état du combat en session
            $request->getSession()->set('combat_' . $combatId, $fullCombatState);

            // Adapter la réponse pour le frontend avec les vrais skills des héros
            $response = [
                'id' => $combatId,
                'fighters' => array_map(function($fighter) use ($fullCombatState) {
                    $hero = $fullCombatState['heroMapping'][$fighter['id']] ?? null;
                    
                    return [
                        'id' => $fighter['id'],
                        'name' => $fighter['name'],
                        'hp' => $fighter['hp'],
                        'maxHp' => $fighter['maxHp'],
                        'alive' => $fighter['isAlive'],
                        'team' => $fighter['team'],
                        'attack' => $fighter['attack'],
                        'defense' => $fighter['defense'],
                        'speed' => $fighter['speed'],
                        'resistance' => $fighter['resistance'],
                        'star' => $fighter['star'],
                        'type' => $fighter['type'],
                        'skills' => $hero ? $this->getHeroRealSkills($hero, $fighter['id'], $fullCombatState) : $this->getDefaultSkills()
                    ];
                }, $fullCombatState['fighters']),
                'currentTurn' => $this->getCurrentTurnIndex($fullCombatState),
                'phase' => 'battle',
                'logs' => $fullCombatState['logs'],
                'winner' => null
            ];

            return new JsonResponse($response);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Combat initialization failed: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les vrais skills d'un héros avec leurs cooldowns actuels
     */
    private function getHeroRealSkills(\App\Entity\Hero $hero, int $fighterId, array $combatState): array
    {
        try {
            // Récupérer les skills du héros depuis la BDD
            $heroSkills = $this->heroSkillRepository->findBy(['hero' => $hero]);
            
            $skills = [];
            foreach ($heroSkills as $heroSkill) {
                // Récupérer le cooldown actuel pour ce fighter
                $currentCooldown = $combatState['skillCooldowns'][$fighterId][$heroSkill->getId()] ?? 0;
                
                $skills[] = [
                    'id' => $heroSkill->getId(),
                    'name' => $heroSkill->getName(),
                    'description' => $heroSkill->getDescription(),
                    'cooldown' => $currentCooldown, // Cooldown actuel
                    'max_cooldown' => $heroSkill->getCooldown(), // Cooldown max après utilisation
                    'initial_cooldown' => $heroSkill->getInitialCooldown(),
                    'multiplicator' => $heroSkill->getMultiplicator(),
                    'scaling' => $heroSkill->getScaling(),
                    'hits_number' => $heroSkill->getHitsNumber(),
                    'is_passive' => $heroSkill->getIsPassive(),
                    'targeting' => $heroSkill->getTargeting(),
                    'targeting_team' => $heroSkill->getTargetingTeam(),
                    'does_damage' => $heroSkill->getDoesDamage(),
                    // Un skill est disponible si : pas en cooldown ET pas passif
                    'available' => $currentCooldown <= 0 && !$heroSkill->getIsPassive()
                ];
            }
            
            // Si aucun skill n'est trouvé, ajouter une attaque basique par défaut
            if (empty($skills)) {
                return $this->getDefaultSkills();
            }
            $fighter = null;
            foreach ($combatState['fighters'] as $f) {
                if ($f['id'] === $fighterId) {
                    $fighter = $f;
                    break;
                }
            }
            
            if ($fighter) {
                foreach ($skills as &$skill) {
                    // Vérifier le silence (ne peut utiliser que le premier skill)
                    if ($this->effectService->isSilenced($fighter)) {
                        $isFirstSkill = ($skill === reset($skills));
                        $skill['available'] = $skill['available'] && $isFirstSkill;
                        if (!$isFirstSkill) {
                            $skill['silenced'] = true;
                        }
                    }
                    
                    // Vérifier l'étourdissement ou le gel
                    if ($this->effectService->isStunnedOrFrozen($fighter)) {
                        $skill['available'] = false;
                        $skill['stunned'] = true;
                    }
                }
            }
            
            return $skills;
        } catch (\Exception $e) {
            // En cas d'erreur, retourner les skills par défaut
            return $this->getDefaultSkills();
        }
    }

    /**
     * Retourne les skills par défaut si aucun skill n'est trouvé
     */
    private function getDefaultSkills(): array
    {
        return [
            [
                'id' => 'basic_attack',
                'name' => 'Attaque Basique',
                'description' => 'Une attaque physique simple',
                'cooldown' => 0,
                'max_cooldown' => 0,
                'multiplicator' => 1.0,
                'does_damage' => true,
                'targeting_team' => 'enemy',
                'is_passive' => false,
                'available' => true
            ]
        ];
    }

    #[Route('/combat/action', name: 'combat_action', methods: ['POST', 'OPTIONS'])]
    public function executeAction(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['combatId']) || !isset($data['skillId']) || !isset($data['targetId'])) {
                return new JsonResponse(['error' => 'Combat ID, skill ID and target ID are required'], Response::HTTP_BAD_REQUEST);
            }

            // Récupérer l'état du combat depuis la session
            $combatState = $request->getSession()->get('combat_' . $data['combatId']);
            
            if (!$combatState) {
                return new JsonResponse(['error' => 'Combat not found'], Response::HTTP_NOT_FOUND);
            }

            // Vérifier si le combat est terminé
            if ($this->combatRulesService->isCombatFinished($combatState['fighters'])) {
                return new JsonResponse(['error' => 'Combat is already finished'], Response::HTTP_BAD_REQUEST);
            }

            // Trouver l'attaquant et la cible
            $attacker = null;
            $target = null;
            
            foreach ($combatState['fighters'] as &$fighter) {
                if ($fighter['id'] == $data['targetId']) {
                    $target = &$fighter;
                }
            }

            // Déterminer l'attaquant actuel selon l'ordre de tour
            $currentTurnIndex = $this->getCurrentTurnIndex($combatState);
            if ($currentTurnIndex !== null && isset($combatState['fighters'][$currentTurnIndex])) {
                $attacker = &$combatState['fighters'][$currentTurnIndex];
            }

            if (!$attacker || !$target) {
                return new JsonResponse(['error' => 'Invalid attacker or target'], Response::HTTP_BAD_REQUEST);
            }

            if (!$attacker['isAlive'] || !$target['isAlive']) {
                return new JsonResponse(['error' => 'Attacker or target is not alive'], Response::HTTP_BAD_REQUEST);
            }

            // Récupérer le skill utilisé depuis la BDD
            $skill = null;
            if (is_numeric($data['skillId'])) {
                $skill = $this->heroSkillRepository->find($data['skillId']);
                
                // Vérifier si le skill est disponible
                if ($skill) {
                    // Vérifier si c'est un skill passif
                    if ($skill->getIsPassive()) {
                        return new JsonResponse(['error' => 'Cannot use passive skills'], Response::HTTP_BAD_REQUEST);
                    }
                    
                    // Vérifier le cooldown
                    $currentCooldown = $combatState['skillCooldowns'][$attacker['id']][$skill->getId()] ?? 0;
                    if ($currentCooldown > 0) {
                        return new JsonResponse(['error' => 'Skill is on cooldown'], Response::HTTP_BAD_REQUEST);
                    }
                }
            }

            // Exécuter l'action avec le vrai skill
            $this->executeSkillAction($skill, $attacker, $target, $combatState);

            // Passer au tour suivant et gérer les cooldowns
            $this->advanceTurn($combatState);

            // Vérifier si le combat est terminé
            if ($this->combatRulesService->isCombatFinished($combatState['fighters'])) {
                $combatState['winner'] = $this->combatRulesService->determineWinner($combatState['fighters']);
                $combatState['logs'][] = [
                    'message' => "Combat terminé ! L'équipe {$combatState['winner']} gagne !",
                    'timestamp' => time()
                ];
            }

            // Sauvegarder l'état mis à jour
            $request->getSession()->set('combat_' . $data['combatId'], $combatState);

            // Adapter la réponse pour le frontend avec les vrais skills
            $response = [
                'id' => $data['combatId'],
                'fighters' => array_map(function($fighter) use ($combatState) {
                    $hero = $combatState['heroMapping'][$fighter['id']] ?? null;
                    
                    return [
                        'id' => $fighter['id'],
                        'name' => $fighter['name'],
                        'hp' => $fighter['hp'],
                        'maxHp' => $fighter['maxHp'],
                        'alive' => $fighter['isAlive'],
                        'team' => $fighter['team'],
                        'attack' => $fighter['attack'],
                        'defense' => $fighter['defense'],
                        'speed' => $fighter['speed'],
                        'resistance' => $fighter['resistance'],
                        'star' => $fighter['star'],
                        'type' => $fighter['type'],
                        'skills' => $hero ? $this->getHeroRealSkills($hero, $fighter['id'], $combatState) : $this->getDefaultSkills()
                    ];
                }, $combatState['fighters']),
                'currentTurn' => $this->getCurrentTurnIndex($combatState),
                'phase' => $combatState['phase'] ?? 'battle',
                'logs' => array_slice($combatState['logs'], -10),
                'winner' => $combatState['winner'] ?? null
            ];

            return new JsonResponse($response);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Action execution failed: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Exécute une action avec un vrai skill de la BDD
     */
    private function executeSkillAction($skill, array &$attacker, array &$target, array &$combatState): bool
    {
        if (!$skill) {
            return $this->executeBasicAttack($attacker, $target, $combatState);
        }

        $skillName = $skill->getName();
        $multiplicator = $skill->getMultiplicator();
        $doesDamage = $skill->getDoesDamage();
        
        // Mettre le skill en cooldown après utilisation
        $combatState['skillCooldowns'][$attacker['id']][$skill->getId()] = $skill->getCooldown();
        
        if ($doesDamage) {
            // Calcul des dégâts avec la nouvelle formule
            $hitsNumber = $skill->getHitsNumber() ?? 1; //Nombre de coups
            $scaling = $this->calculateScaling($skill, $attacker); // Scaling basé sur la stat de l'attaque
            $defenseReduction = 1000 / (1500 + $target['defense']); // Réduction basée sur la défense
            
            $damage = $hitsNumber * ($multiplicator * $scaling * $defenseReduction);
            $totalDamage = max(1, (int)round($damage));
            
            // Vérifier la protection avant d'appliquer les dégâts
            $protector = $this->effectService->findProtector($target, $combatState['fighters']);
            if ($protector) {
                $target = &$protector; // Le protecteur prend les dégâts
                $this->addCombatLog($combatState['logs'], "{$protector['name']} protège {$target['name']} et prend les dégâts à sa place !");
            }
            
            // Appliquer l'effet de bouclier
            $finalDamage = $this->effectService->applyShieldEffect($target, $totalDamage, $combatState['logs']);
            
            $target['hp'] -= $finalDamage;
            
            if ($target['hp'] <= 0) {
                $target['hp'] = 0;
                $target['isAlive'] = false;
                $combatState['logs'][] = [
                    'message' => "{$attacker['name']} utilise {$skillName} sur {$target['name']} et inflige {$finalDamage} dégâts" . ($hitsNumber > 1 ? " en {$hitsNumber} coups" : "") . " ! {$target['name']} est KO !",
                    'timestamp' => time()
                ];
            } else {
                $combatState['logs'][] = [
                    'message' => "{$attacker['name']} utilise {$skillName} sur {$target['name']} et inflige {$finalDamage} dégâts" . ($hitsNumber > 1 ? " en {$hitsNumber} coups" : "") . " ! ({$target['hp']}/{$target['maxHp']} PV restants)",
                    'timestamp' => time()
                ];
            }
            
            // Appliquer l'effet de vol de vie
            $this->effectService->applyLifestealEffect($attacker, $finalDamage, $combatState['logs']);
            
            // Vérifier la contre-attaque
            if ($this->effectService->shouldCounterAttack($target, $combatState['logs'])) {
                // Logique de contre-attaque à implémenter
            }
            
        } else {
            // Skill de support/soin
            $scaling = $this->calculateScaling($skill, $attacker);
            $healAmount = (int)round($multiplicator * $scaling);
            
            // Vérifier l'effet de soins inversés
            $healReverseDamage = $this->effectService->applyHealReverseEffect($target, $healAmount, $combatState['logs']);
            
            if ($healReverseDamage === 0) {
                // Soins normaux
                $target['hp'] += $healAmount;
                
                if ($target['hp'] > $target['maxHp']) {
                    $target['hp'] = $target['maxHp'];
                }
                
                $combatState['logs'][] = [
                    'message' => "{$attacker['name']} utilise {$skillName} sur {$target['name']} et restaure {$healAmount} PV ! ({$target['hp']}/{$target['maxHp']} PV)",
                    'timestamp' => time()
                ];
            }
        }
        $this->effectService->applySkillEffects($skill, $attacker, $target, $combatState['logs'], $combatState['fighters']);
        
        return true;
    }

    private function addCombatLog(array &$logs, string $message): void
    {
        $logs[] = [
            'timestamp' => time(),
            'message' => $message
        ];
    }

    /**
     * Calcule le scaling d'un skill selon les stats de l'attaquant
     */
    private function calculateScaling($skill, array $attacker): float
    {
        $scalingData = json_decode($skill->getScaling(), true);
        
        // Si pas de scaling défini, utiliser l'attaque par défaut
        if (!$scalingData || empty($scalingData)) {
            return $attacker['attack'];
        }
        
        $totalScaling = 0;
        
        // Parcourir chaque scaling défini
        foreach ($scalingData as $stat => $coefficient) {
            switch ($stat) {
                case 'attack':
                case 'atk':
                    $totalScaling += $attacker['attack'] * $coefficient;
                    break;
                case 'defense':
                case 'def':
                    $totalScaling += $attacker['defense'] * $coefficient;
                    break;
                case 'speed':
                case 'vit':
                    $totalScaling += $attacker['speed'] * $coefficient;
                    break;
                case 'resistance':
                case 'res':
                    $totalScaling += $attacker['resistance'] * $coefficient;
                    break;
                case 'hp':
                    $totalScaling += $attacker['maxHp'] * $coefficient;
                    break;
                default:
                    // Si stat inconnue, ignorer
                    break;
            }
        }
        
        // Si aucun scaling valide trouvé, utiliser l'attaque
        return $totalScaling > 0 ? $totalScaling : $attacker['attack'];
    }

    /**
     * Obtient l'index du combattant dont c'est le tour
     */
    private function getCurrentTurnIndex(array $combatState): ?int
    {
        if (!isset($combatState['currentTurnOrder']) || empty($combatState['currentTurnOrder'])) {
            return 0;
        }

        // Trouver le prochain héros vivant qui n'a pas encore joué
        foreach ($combatState['currentTurnOrder'] as $fighterId) {
            if (!($combatState['hasPlayed'][$fighterId] ?? false)) {
                // Trouver l'index de ce fighter
                foreach ($combatState['fighters'] as $index => $fighter) {
                    if ($fighter['id'] === $fighterId && $fighter['isAlive']) {
                        return $index;
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Fait avancer le tour au prochain combattant et gère les cooldowns
     */
    private function advanceTurn(array &$combatState): void
    {
        if (!isset($combatState['currentTurnOrder']) || empty($combatState['currentTurnOrder'])) {
            $combatState['currentTurnOrder'] = $this->combatRulesService->getAttackOrder($combatState['fighters']);
            foreach ($combatState['fighters'] as $fighter) {
                $combatState['hasPlayed'][$fighter['id']] = false;
            }
        }

        // Marquer le combattant actuel comme ayant joué
        $currentTurnIndex = $this->getCurrentTurnIndex($combatState);
        if ($currentTurnIndex !== null && isset($combatState['fighters'][$currentTurnIndex])) {
            $currentFighterId = $combatState['fighters'][$currentTurnIndex]['id'];
            $combatState['hasPlayed'][$currentFighterId] = true;
        }

        // Vérifier si tous les combattants vivants ont joué
        $allPlayed = true;
        foreach ($combatState['currentTurnOrder'] as $fighterId) {
            $fighter = null;
            foreach ($combatState['fighters'] as $f) {
                if ($f['id'] === $fighterId) {
                    $fighter = $f;
                    break;
                }
            }
            
            if ($fighter && $fighter['isAlive'] && !($combatState['hasPlayed'][$fighterId] ?? false)) {
                $allPlayed = false;
                break;
            }
        }

        // Si tous ont joué, recommencer un nouveau tour et réduire les cooldowns
        if ($allPlayed) {
        $combatState['turn'] = ($combatState['turn'] ?? 1) + 1;
        
        // NOUVEAU : Traiter les effets en début de tour
        $this->effectService->processEffects($combatState['fighters'], $combatState['logs']);
        
        $combatState['currentTurnOrder'] = $this->combatRulesService->getAttackOrder($combatState['fighters']);
        
        // Réinitialiser hasPlayed et réduire les cooldowns
        foreach ($combatState['fighters'] as $fighter) {
            if ($fighter['isAlive']) {
                $combatState['hasPlayed'][$fighter['id']] = false;
                
                // Réduire les cooldowns de ce fighter
                if (isset($combatState['skillCooldowns'][$fighter['id']])) {
                    foreach ($combatState['skillCooldowns'][$fighter['id']] as $skillId => $cooldown) {
                        if ($cooldown > 0) {
                            $combatState['skillCooldowns'][$fighter['id']][$skillId] = $cooldown - 1;
                        }
                    }
                }
            }
        }
            $combatState['logs'][] = [
                'message' => "--- Tour {$combatState['turn']} ---",
                'timestamp' => time()
            ];
        }
    }

    #[Route('/combat/test', name: 'combat_test', methods: ['GET', 'OPTIONS'])]
    public function testRoute(Request $request): JsonResponse
    {
        return new JsonResponse(['message' => 'Combat controller is working!']);
    }
}