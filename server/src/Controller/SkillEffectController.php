<?php

namespace App\Controller;

use App\Entity\SkillEffect;
use App\Entity\HeroSkill;
use App\Repository\SkillEffectRepository;
use App\Repository\HeroSkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class SkillEffectController extends AbstractController
{
    #[Route('/skill/effects', name: 'get_skill_effects', methods: ['GET'])]
    public function getEffects(Request $request, SkillEffectRepository $effectRepo): JsonResponse
    {
        $effects = $effectRepo->findAll();
        $data = array_map(function($effect) {
            return [
                'id' => $effect->getId(),
                'skill_id' => $effect->getSkill()->getId(),
                'skill_name' => $effect->getSkill()->getName(),
                'effect_type' => $effect->getEffectType(),
                'value' => $effect->getValue(),
                'chance' => $effect->getChance(),
                'duration' => $effect->getDuration(),
                'scale_on' => $effect->getScaleOn(),
                'target_side' => $effect->getTargetSide(),
                'cumulative' => $effect->isCumulative(),
            ];
        }, $effects);

        return new JsonResponse($data);
    }

    #[Route('/skill/{skillId}/effects', name: 'get_skill_specific_effects', methods: ['GET'])]
    public function getSkillEffects(Request $request, SkillEffectRepository $effectRepo, HeroSkillRepository $skillRepo, int $skillId): JsonResponse
    {
        $skill = $skillRepo->find($skillId);
        if (!$skill) {
            return new JsonResponse(['error' => 'Compétence introuvable'], 404);
        }

        $effects = $effectRepo->findBy(['skill' => $skill]);
        $data = array_map(function($effect) {
            return [
                'id' => $effect->getId(),
                'effect_type' => $effect->getEffectType(),
                'value' => $effect->getValue(),
                'chance' => $effect->getChance(),
                'duration' => $effect->getDuration(),
                'scale_on' => $effect->getScaleOn(),
                'target_side' => $effect->getTargetSide(),
                'cumulative' => $effect->isCumulative(),
            ];
        }, $effects);

        return new JsonResponse($data);
    }

    #[Route('/skill/effect/{id}', name: 'get_skill_effect', methods: ['GET'])]
    public function getEffect(Request $request, SkillEffectRepository $effectRepo, int $id): JsonResponse
    {
        $effect = $effectRepo->find($id);
        if (!$effect) {
            return new JsonResponse(['error' => 'Effet introuvable'], 404);
        }

        $data = [
            'id' => $effect->getId(),
            'skill_id' => $effect->getSkill()->getId(),
            'skill_name' => $effect->getSkill()->getName(),
            'effect_type' => $effect->getEffectType(),
            'value' => $effect->getValue(),
            'chance' => $effect->getChance(),
            'duration' => $effect->getDuration(),
            'scale_on' => $effect->getScaleOn(),
            'target_side' => $effect->getTargetSide(),
            'cumulative' => $effect->isCumulative(),
        ];

        return new JsonResponse($data);
    }

    #[Route('/skill/effect/add', name: 'add_skill_effect', methods: ['POST'])]
    public function addEffect(Request $request, EntityManagerInterface $em, HeroSkillRepository $skillRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $skill = $skillRepo->find($data['skillId'] ?? null);
        if (!$skill) {
            return new JsonResponse(['error' => 'Compétence introuvable'], 404);
        }

        $effect = new SkillEffect();
        $effect->setSkill($skill);
        $effect->setEffectType($data['effect_type'] ?? '');
        $effect->setValue($data['value'] ?? 0.0);
        $effect->setChance($data['chance'] ?? 100);
        $effect->setDuration($data['duration'] ?? 1);
        $effect->setScaleOn($data['scale_on'] ?? '{}');
        $effect->setTargetSide($data['target_side'] ?? 'enemy');
        $effect->setCumulative($data['cumulative'] ?? false);

        $em->persist($effect);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'id' => $effect->getId()
        ], 201);
    }

    #[Route('/skill/effect/{id}/edit', name: 'edit_skill_effect', methods: ['PUT', 'PATCH'])]
    public function editEffect(Request $request, EntityManagerInterface $em, SkillEffectRepository $effectRepo, HeroSkillRepository $skillRepo, int $id): JsonResponse
    {
        $effect = $effectRepo->find($id);
        if (!$effect) {
            return new JsonResponse(['error' => 'Effet introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['skillId'])) {
            $skill = $skillRepo->find($data['skillId']);
            if (!$skill) {
                return new JsonResponse(['error' => 'Compétence introuvable'], 404);
            }
            $effect->setSkill($skill);
        }

        if (isset($data['effect_type'])) {
            $effect->setEffectType($data['effect_type']);
        }
        if (isset($data['value'])) {
            $effect->setValue($data['value']);
        }
        if (isset($data['chance'])) {
            $effect->setChance($data['chance']);
        }
        if (isset($data['duration'])) {
            $effect->setDuration($data['duration']);
        }
        if (isset($data['scale_on'])) {
            $effect->setScaleOn($data['scale_on']);
        }
        if (isset($data['target_side'])) {
            $effect->setTargetSide($data['target_side']);
        }
        if (isset($data['cumulative'])) {
            $effect->setCumulative($data['cumulative']);
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/skill/effect/{id}/delete', name: 'delete_skill_effect', methods: ['DELETE'])]
    public function deleteEffect(Request $request, EntityManagerInterface $em, SkillEffectRepository $effectRepo, int $id): JsonResponse
    {
        $effect = $effectRepo->find($id);
        if (!$effect) {
            return new JsonResponse(['error' => 'Effet introuvable'], 404);
        }

        $em->remove($effect);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}