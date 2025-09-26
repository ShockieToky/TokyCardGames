<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;

class CombatController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // CORRECTION : Changé /combat/start en /api/combat/start
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
                    $teamAHeroes[] = [
                        'id' => $hero->getId(),
                        'name' => $hero->getName(),
                        'hp' => $hero->getHP(),
                        'maxHp' => $hero->getHP(),
                        'alive' => true,
                        'team' => 'A',
                        'skills' => [
                            ['id' => 1, 'name' => 'Attaque Basique', 'cooldown' => 0],
                            ['id' => 2, 'name' => 'Coup Puissant', 'cooldown' => 0],
                            ['id' => 3, 'name' => 'Soins', 'cooldown' => 0],
                            ['id' => 4, 'name' => 'Attaque de Zone', 'cooldown' => 0]
                        ]
                    ];
                }
            }
            
            $teamBHeroes = [];
            foreach ($data['teamB'] as $heroId) {
                $hero = $heroRepository->find($heroId);
                if ($hero) {
                    $teamBHeroes[] = [
                        'id' => $hero->getId(),
                        'name' => $hero->getName(),
                        'hp' => $hero->getHP(),
                        'maxHp' => $hero->getHP(),
                        'alive' => true,
                        'team' => 'B',
                        'skills' => [
                            ['id' => 1, 'name' => 'Attaque Basique', 'cooldown' => 0],
                            ['id' => 2, 'name' => 'Coup Puissant', 'cooldown' => 0],
                            ['id' => 3, 'name' => 'Soins', 'cooldown' => 0],
                            ['id' => 4, 'name' => 'Attaque de Zone', 'cooldown' => 0]
                        ]
                    ];
                }
            }

            if (count($teamAHeroes) !== 4 || count($teamBHeroes) !== 4) {
                return new JsonResponse(['error' => 'Each team must have exactly 4 heroes'], Response::HTTP_BAD_REQUEST);
            }

            // État de combat simple
            $combatState = [
                'id' => uniqid('combat_'),
                'fighters' => array_merge($teamAHeroes, $teamBHeroes),
                'currentTurn' => 0,
                'phase' => 'selection',
                'logs' => [
                    ['message' => 'Le combat commence !', 'timestamp' => time()]
                ],
                'winner' => null
            ];

            return new JsonResponse($combatState);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Combat initialization failed: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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

            // Simulation d'une action réussie
            return new JsonResponse([
                'id' => $data['combatId'],
                'fighters' => [],
                'currentTurn' => 0,
                'phase' => 'selection',
                'logs' => [
                    ['message' => 'Action exécutée avec succès', 'timestamp' => time()]
                ],
                'winner' => null
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Action execution failed: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/combat/test', name: 'combat_test', methods: ['GET', 'OPTIONS'])]
    public function testRoute(Request $request): JsonResponse
    {
        return new JsonResponse(['message' => 'Combat controller is working!']);
    }
}