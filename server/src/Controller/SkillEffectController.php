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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SkillEffectController extends AbstractController
{
    // Constantes pour les valeurs par défaut
    private const DEFAULT_EFFECT_TYPE = 'buff_attack';
    private const DEFAULT_VALUE = 0.0;
    private const DEFAULT_CHANCE = 100;
    private const DEFAULT_DURATION = 2;
    private const DEFAULT_SCALE_ON = '{}';
    private const DEFAULT_TARGET_SIDE = 'ally';
    private const DEFAULT_CUMULATIVE = false;

    #[Route('/skill/effects', name: 'get_skill_effects', methods: ['GET'])]
    public function getEffects(SkillEffectRepository $effectRepo): JsonResponse
    {
        $effects = $effectRepo->findAll();
        $data = array_map(fn($effect) => $this->formatEffectData($effect), $effects);

        return $this->json($data);
    }

    #[Route('/skill/{skillId}/effects', name: 'get_skill_specific_effects', methods: ['GET'])]
    public function getSkillEffects(SkillEffectRepository $effectRepo, HeroSkillRepository $skillRepo, int $skillId): JsonResponse
    {
        $skill = $skillRepo->find($skillId);
        if (!$skill) {
            return $this->json(['success' => false, 'error' => 'Compétence introuvable'], 404);
        }

        $effects = $effectRepo->findBy(['skill' => $skill]);
        $data = array_map(fn($effect) => $this->formatEffectDataSimple($effect), $effects);

        return $this->json($data);
    }

    #[Route('/skill/effect/{id}', name: 'get_skill_effect', methods: ['GET'])]
    public function getEffect(SkillEffectRepository $effectRepo, int $id): JsonResponse
    {
        $effect = $effectRepo->find($id);
        if (!$effect) {
            return $this->json(['success' => false, 'error' => 'Effet introuvable'], 404);
        }

        return $this->json($this->formatEffectData($effect));
    }

    #[Route('/skill/effect/add', name: 'add_skill_effect', methods: ['POST'])]
    public function addEffect(Request $request, EntityManagerInterface $em, HeroSkillRepository $skillRepo): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'error' => 'JSON invalide: ' . json_last_error_msg()
                ], 400);
            }
            
            if (!isset($data['skillId'])) {
                return $this->json(['success' => false, 'error' => 'skillId requis'], 400);
            }
            
            $skill = $skillRepo->find($data['skillId']);
            if (!$skill) {
                return $this->json([
                    'success' => false, 
                    'error' => 'Compétence introuvable (ID: ' . $data['skillId'] . ')'
                ], 404);
            }
            
            $effect = new SkillEffect();
            $effect->setSkill($skill);
            
            $this->updateEffectFromData($effect, $data);
            
            $em->persist($effect);
            $em->flush();
            
            return $this->json([
                'success' => true, 
                'id' => $effect->getId(),
                'message' => 'Effet ajouté avec succès',
                'description' => $effect->getDescription()
            ], 201);
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'ajout d\'un effet: ' . $e->getMessage());
            
            return $this->json([
                'success' => false, 
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/skill/effect/{id}/edit', name: 'edit_skill_effect', methods: ['PUT', 'PATCH'])]
    public function editEffect(Request $request, EntityManagerInterface $em, SkillEffectRepository $effectRepo, HeroSkillRepository $skillRepo, int $id): JsonResponse
    {
        try {
            $effect = $effectRepo->find($id);
            if (!$effect) {
                return $this->json(['success' => false, 'error' => 'Effet introuvable'], 404);
            }

            $data = json_decode($request->getContent(), true);
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'error' => 'JSON invalide: ' . json_last_error_msg()
                ], 400);
            }

            // Traitement du changement de compétence
            if (isset($data['skillId'])) {
                $skill = $skillRepo->find($data['skillId']);
                if (!$skill) {
                    return $this->json([
                        'success' => false, 
                        'error' => 'Compétence introuvable (ID: ' . $data['skillId'] . ')'
                    ], 404);
                }
                $effect->setSkill($skill);
            }

            $this->updateEffectFromData($effect, $data);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Effet mis à jour avec succès',
                'description' => $effect->getDescription()
            ]);
        } catch (\Exception $e) {
            error_log('Erreur lors de la mise à jour d\'un effet: ' . $e->getMessage());
            
            return $this->json([
                'success' => false, 
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/skill/effect/{id}/delete', name: 'delete_skill_effect', methods: ['DELETE'])]
    public function deleteEffect(EntityManagerInterface $em, SkillEffectRepository $effectRepo, int $id): JsonResponse
    {
        try {
            $effect = $effectRepo->find($id);
            if (!$effect) {
                return $this->json(['success' => false, 'error' => 'Effet introuvable'], 404);
            }

            $skillName = $effect->getSkill()->getName();
            $effectDescription = $effect->getDescription();
            
            $em->remove($effect);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => "Effet \"$effectDescription\" supprimé de la compétence \"$skillName\""
            ]);
        } catch (\Exception $e) {
            error_log('Erreur lors de la suppression d\'un effet: ' . $e->getMessage());
            
            return $this->json([
                'success' => false, 
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Met à jour un effet avec les données reçues
     */
    private function updateEffectFromData(SkillEffect $effect, array $data): void
    {
        $effect->setEffectType($data['effect_type'] ?? self::DEFAULT_EFFECT_TYPE);
        
        // Conversion sécurisée des valeurs numériques
        $effect->setValue(isset($data['value']) ? (float)$data['value'] : self::DEFAULT_VALUE);
        $effect->setChance(isset($data['chance']) ? (int)$data['chance'] : self::DEFAULT_CHANCE);
        $effect->setDuration(isset($data['duration']) ? (int)$data['duration'] : self::DEFAULT_DURATION);
        
        // Sécuriser scale_on en s'assurant que c'est un JSON valide
        if (isset($data['scale_on'])) {
            $scaleOn = $data['scale_on'];
            if (is_string($scaleOn)) {
                // Vérifier si c'est déjà un JSON valide
                json_decode($scaleOn);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Si ce n'est pas un JSON valide, utiliser le format par défaut
                    $scaleOn = self::DEFAULT_SCALE_ON;
                }
            } elseif (is_array($scaleOn)) {
                // Convertir le tableau en JSON
                $scaleOn = json_encode($scaleOn);
            } else {
                // Valeur invalide, utiliser la valeur par défaut
                $scaleOn = self::DEFAULT_SCALE_ON;
            }
            $effect->setScaleOn($scaleOn);
        } else {
            $effect->setScaleOn(self::DEFAULT_SCALE_ON);
        }
        
        $effect->setTargetSide($data['target_side'] ?? self::DEFAULT_TARGET_SIDE);
        $effect->setCumulative(isset($data['cumulative']) ? (bool)$data['cumulative'] : self::DEFAULT_CUMULATIVE);
    }
    
    /**
     * Formatte les données d'un effet pour l'API (version complète)
     */
    private function formatEffectData(SkillEffect $effect): array
    {
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
            'description' => $effect->getDescription()
        ];
    }
    
    /**
     * Formatte les données d'un effet pour l'API (version simplifiée)
     */
    private function formatEffectDataSimple(SkillEffect $effect): array
    {
        return [
            'id' => $effect->getId(),
            'effect_type' => $effect->getEffectType(),
            'value' => $effect->getValue(),
            'chance' => $effect->getChance(),
            'duration' => $effect->getDuration(),
            'scale_on' => $effect->getScaleOn(),
            'target_side' => $effect->getTargetSide(),
            'cumulative' => $effect->isCumulative(),
            'description' => $effect->getDescription()
        ];
    }
}