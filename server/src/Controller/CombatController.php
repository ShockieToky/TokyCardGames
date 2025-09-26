<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\Combat\CombatRulesService;
use App\Dto\CombatState;

/**
 * @Route("/api/combat")
 */
class CombatController extends AbstractController
{
	private $combatRulesService;

	public function __construct(CombatRulesService $combatRulesService)
	{
		$this->combatRulesService = $combatRulesService;
	}

	/**
	 * @Route("/start", name="combat_start", methods={"POST"})
	 */
	public function start(Request $request): JsonResponse
	{
		$data = json_decode($request->getContent(), true);
		$teamA = $data['teamA'] ?? [];
		$teamB = $data['teamB'] ?? [];

		// Démarre un nouveau combat et retourne l'état initial
		$combatState = $this->combatRulesService->startCombat($teamA, $teamB);
		return new JsonResponse($combatState, 200);
	}

	/**
	 * @Route("/action", name="combat_action", methods={"POST"})
	 */
	public function action(Request $request): JsonResponse
	{
		$data = json_decode($request->getContent(), true);
		$combatId = $data['combatId'] ?? null;
		$actorId = $data['actorId'] ?? null;
		$skillId = $data['skillId'] ?? null;
		$targetId = $data['targetId'] ?? null;

		// Applique l'action du joueur et retourne le nouvel état du combat
		$combatState = $this->combatRulesService->processAction($combatId, $actorId, $skillId, $targetId);
		return new JsonResponse($combatState, 200);
	}

	/**
	 * @Route("/state/{combatId}", name="combat_state", methods={"GET"})
	 */
	public function state(string $combatId): JsonResponse
	{
		// Récupère l'état courant du combat
		$combatState = $this->combatRulesService->getCombatState($combatId);
		return new JsonResponse($combatState, 200);
	}

	/**
	 * @Route("/list", name="combat_list", methods={"GET"})
	 */
	public function list(): JsonResponse
	{
		// Liste tous les combats en cours (optionnel)
		$combats = $this->combatRulesService->listCombats();
		return new JsonResponse($combats, 200);
	}
}
