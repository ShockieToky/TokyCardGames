<?php

namespace App\Controller;

use App\Entity\HeroSkill;
use App\Entity\Hero;
use App\Repository\HeroSkillRepository;
use App\Repository\HeroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class HeroSkillController extends AbstractController
{
    #[Route('/hero/skills', name: 'get_hero_skills', methods: ['GET', 'OPTIONS'])]
    public function getSkills(Request $request, HeroSkillRepository $skillRepo): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        $skills = $skillRepo->findAll();
        $data = array_map(function($skill) {
            return [
                'id' => $skill->getId(),
                'heroId' => $skill->getHero()->getId(),
                'heroName' => $skill->getHero()->getName(),
                'name' => $skill->getName(),
                'description' => $skill->getDescription(),
                'multiplicator' => $skill->getMultiplicator(),
                'scaling' => $skill->getScaling(),
                'hits_number' => $skill->getHitsNumber(),
                'cooldown' => $skill->getCooldown(),
                'initial_cooldown' => $skill->getInitialCooldown(),
                'is_passive' => $skill->getIsPassive(),
                'targeting' => $skill->getTargeting(),
                'targeting_team' => $skill->getTargetingTeam(),
                'does_damage' => $skill->getDoesDamage(),
            ];
        }, $skills);

        return new JsonResponse($data, 200, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }

    #[Route('/hero/{heroId}/skills', name: 'get_hero_specific_skills', methods: ['GET', 'OPTIONS'])]
    public function getHeroSkills(Request $request, HeroSkillRepository $skillRepo, HeroRepository $heroRepo, int $heroId): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        $hero = $heroRepo->find($heroId);
        if (!$hero) {
            return new JsonResponse(['error' => 'Héros introuvable'], 404, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $skills = $skillRepo->findBy(['hero' => $hero]);
        $data = array_map(function($skill) {
            return [
                'id' => $skill->getId(),
                'name' => $skill->getName(),
                'description' => $skill->getDescription(),
                'multiplicator' => $skill->getMultiplicator(),
                'scaling' => $skill->getScaling(),
                'hits_number' => $skill->getHitsNumber(),
                'cooldown' => $skill->getCooldown(),
                'initial_cooldown' => $skill->getInitialCooldown(),
                'is_passive' => $skill->getIsPassive(),
                'targeting' => $skill->getTargeting(),
                'targeting_team' => $skill->getTargetingTeam(),
                'does_damage' => $skill->getDoesDamage(),
            ];
        }, $skills);

        return new JsonResponse($data, 200, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }

    #[Route('/hero/skill/{id}', name: 'get_hero_skill', methods: ['GET', 'OPTIONS'])]
    public function getSkill(Request $request, HeroSkillRepository $skillRepo, int $id): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        $skill = $skillRepo->find($id);
        if (!$skill) {
            return new JsonResponse(['error' => 'Compétence introuvable'], 404, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $data = [
            'id' => $skill->getId(),
            'heroId' => $skill->getHero()->getId(),
            'heroName' => $skill->getHero()->getName(),
            'name' => $skill->getName(),
            'description' => $skill->getDescription(),
            'multiplicator' => $skill->getMultiplicator(),
            'scaling' => $skill->getScaling(),
            'hits_number' => $skill->getHitsNumber(),
            'cooldown' => $skill->getCooldown(),
            'initial_cooldown' => $skill->getInitialCooldown(),
            'is_passive' => $skill->getIsPassive(),
            'targeting' => $skill->getTargeting(),
            'targeting_team' => $skill->getTargetingTeam(),
            'does_damage' => $skill->getDoesDamage(),
        ];

        return new JsonResponse($data, 200, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }

    #[Route('/hero/skill/add', name: 'add_hero_skill', methods: ['POST', 'OPTIONS'])]
    public function addSkill(Request $request, EntityManagerInterface $em, HeroRepository $heroRepo): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        $data = json_decode($request->getContent(), true);

        $hero = $heroRepo->find($data['heroId'] ?? null);
        if (!$hero) {
            return new JsonResponse(['error' => 'Héros introuvable'], 404, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $skill = new HeroSkill();
        $skill->setHero($hero);
        $skill->setName($data['name'] ?? '');
        $skill->setDescription($data['description'] ?? '');
        $skill->setMultiplicator($data['multiplicator'] ?? 1.0);
        $skill->setScaling($data['scaling'] ?? '{}');
        $skill->setHitsNumber($data['hits_number'] ?? 1);
        $skill->setCooldown($data['cooldown'] ?? 0);
        $skill->setInitialCooldown($data['initial_cooldown'] ?? 0);
        $skill->setIsPassive($data['is_passive'] ?? false);
        $skill->setTargeting($data['targeting'] ?? '{}');
        $skill->setTargetingTeam($data['targeting_team'] ?? 'enemy');
        $skill->setDoesDamage($data['does_damage'] ?? true);

        $em->persist($skill);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'id' => $skill->getId()
        ], 201, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }

    #[Route('/hero/skill/{id}/edit', name: 'edit_hero_skill', methods: ['PUT', 'PATCH', 'OPTIONS'])]
    public function editSkill(Request $request, EntityManagerInterface $em, HeroSkillRepository $skillRepo, HeroRepository $heroRepo, int $id): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'PUT, PATCH, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        $skill = $skillRepo->find($id);
        if (!$skill) {
            return new JsonResponse(['error' => 'Compétence introuvable'], 404, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['heroId'])) {
            $hero = $heroRepo->find($data['heroId']);
            if (!$hero) {
                return new JsonResponse(['error' => 'Héros introuvable'], 404, [
                    'Access-Control-Allow-Origin' => 'http://localhost:3000',
                    'Access-Control-Allow-Credentials' => 'true'
                ]);
            }
            $skill->setHero($hero);
        }

        if (isset($data['name'])) {
            $skill->setName($data['name']);
        }
        if (isset($data['description'])) {
            $skill->setDescription($data['description']);
        }
        if (isset($data['multiplicator'])) {
            $skill->setMultiplicator($data['multiplicator']);
        }
        if (isset($data['scaling'])) {
            $skill->setScaling($data['scaling']);
        }
        if (isset($data['hits_number'])) {
            $skill->setHitsNumber($data['hits_number']);
        }
        if (isset($data['cooldown'])) {
            $skill->setCooldown($data['cooldown']);
        }
        if (isset($data['initial_cooldown'])) {
            $skill->setInitialCooldown($data['initial_cooldown']);
        }
        if (isset($data['is_passive'])) {
            $skill->setIsPassive($data['is_passive']);
        }
        if (isset($data['targeting'])) {
            $skill->setTargeting($data['targeting']);
        }
        if (isset($data['targeting_team'])) {
            $skill->setTargetingTeam($data['targeting_team']);
        }
        if (isset($data['does_damage'])) {
            $skill->setDoesDamage($data['does_damage']);
        }

        $em->flush();

        return new JsonResponse(['success' => true], 200, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }

    #[Route('/hero/skill/{id}/delete', name: 'delete_hero_skill', methods: ['DELETE', 'OPTIONS'])]
    public function deleteSkill(Request $request, EntityManagerInterface $em, HeroSkillRepository $skillRepo, int $id): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        $skill = $skillRepo->find($id);
        if (!$skill) {
            return new JsonResponse(['error' => 'Compétence introuvable'], 404, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $em->remove($skill);
        $em->flush();

        return new JsonResponse(['success' => true], 200, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }
}