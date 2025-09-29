<?php

namespace App\Controller;

use App\Entity\SkillEffect;
use App\Repository\SkillEffectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class SkillEffectController extends AbstractController
{
    #[Route('/skill/effects', name: 'get_skill_effects', methods: ['GET'])]
    public function getEffects(SkillEffectRepository $effectRepo): JsonResponse
    {
        $effects = $effectRepo->findAll();
        $data = array_map(fn($effect) => $this->formatEffectData($effect), $effects);

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
    public function addEffect(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'error' => 'JSON invalide: ' . json_last_error_msg()
                ], 400);
            }
            
            // Vérification des champs obligatoires
            if (!isset($data['name']) || empty($data['name'])) {
                return $this->json(['success' => false, 'error' => 'Le nom de l\'effet est requis'], 400);
            }
            
            $effect = new SkillEffect();
            $effect->setName($data['name']);
            $effect->setDescription($data['description'] ?? '');
            
            $em->persist($effect);
            $em->flush();
            
            return $this->json([
                'success' => true, 
                'id' => $effect->getId(),
                'message' => 'Effet ajouté avec succès',
                'effect' => $this->formatEffectData($effect)
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
    public function editEffect(Request $request, EntityManagerInterface $em, SkillEffectRepository $effectRepo, int $id): JsonResponse
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

            // Mise à jour des champs
            if (isset($data['name']) && !empty($data['name'])) {
                $effect->setName($data['name']);
            }
            
            if (isset($data['description'])) {
                $effect->setDescription($data['description']);
            }

            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Effet mis à jour avec succès',
                'effect' => $this->formatEffectData($effect)
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

            $effectName = $effect->getName();
            
            $em->remove($effect);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => "Effet \"$effectName\" supprimé avec succès"
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
     * Formatte les données d'un effet pour l'API
     */
    private function formatEffectData(SkillEffect $effect): array
    {
        return [
            'id' => $effect->getId(),
            'name' => $effect->getName(),
            'description' => $effect->getDescription(),
            'createdAt' => $effect->getCreatedAt()->format('Y-m-d H:i:s')
        ];
    }
}