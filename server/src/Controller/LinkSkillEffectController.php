<?php

namespace App\Controller;

use App\Entity\LinkSkillEffect;
use App\Entity\HeroSkill;
use App\Entity\SkillEffect;
use App\Repository\LinkSkillEffectRepository;
use App\Repository\HeroSkillRepository;
use App\Repository\SkillEffectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/link/skill/effect')]
final class LinkSkillEffectController extends AbstractController
{
    #[Route('/all', name: 'get_all_skill_effect_links', methods: ['GET'])]
    public function getAllLinks(LinkSkillEffectRepository $linkRepo): JsonResponse
    {
        $links = $linkRepo->findAll();
        $data = array_map(fn($link) => $this->formatLinkData($link), $links);
        
        return $this->json($data);
    }
    
    #[Route('/skill/{skillId}', name: 'get_skill_effect_links', methods: ['GET'])]
    public function getSkillLinks(LinkSkillEffectRepository $linkRepo, HeroSkillRepository $skillRepo, int $skillId): JsonResponse
    {
        $skill = $skillRepo->find($skillId);
        if (!$skill) {
            return $this->json(['success' => false, 'error' => 'Compétence introuvable'], 404);
        }
        
        $links = $linkRepo->findBy(['skill' => $skill]);
        $data = array_map(fn($link) => $this->formatLinkData($link), $links);
        
        return $this->json($data);
    }
    
    #[Route('/{id}', name: 'get_skill_effect_link', methods: ['GET'])]
    public function getLink(LinkSkillEffectRepository $linkRepo, int $id): JsonResponse
    {
        $link = $linkRepo->find($id);
        if (!$link) {
            return $this->json(['success' => false, 'error' => 'Lien compétence-effet introuvable'], 404);
        }
        
        return $this->json($this->formatLinkData($link));
    }
    
    #[Route('/add', name: 'add_skill_effect_link', methods: ['POST'])]
    public function createLink(
        Request $request, 
        EntityManagerInterface $em, 
        HeroSkillRepository $skillRepo,
        SkillEffectRepository $effectRepo
    ): JsonResponse
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
            if (!isset($data['skillId']) || !isset($data['effectId'])) {
                return $this->json([
                    'success' => false, 
                    'error' => 'skillId et effectId sont requis'
                ], 400);
            }
            
            $skill = $skillRepo->find($data['skillId']);
            if (!$skill) {
                return $this->json([
                    'success' => false, 
                    'error' => 'Compétence introuvable (ID: ' . $data['skillId'] . ')'
                ], 404);
            }
            
            $effect = $effectRepo->find($data['effectId']);
            if (!$effect) {
                return $this->json([
                    'success' => false, 
                    'error' => 'Effet introuvable (ID: ' . $data['effectId'] . ')'
                ], 404);
            }
            
            $link = new LinkSkillEffect();
            $link->setSkill($skill);
            $link->setEffect($effect);
            
            // Paramètres optionnels avec valeurs par défaut
            $link->setDuration($data['duration'] ?? 1);
            $link->setAccuracy($data['accuracy'] ?? 100);
            $link->setValue($data['value'] ?? 0.0);
            
            $em->persist($link);
            $em->flush();
            
            return $this->json([
                'success' => true, 
                'id' => $link->getId(),
                'message' => 'Lien compétence-effet ajouté avec succès',
                'link' => $this->formatLinkData($link)
            ], 201);
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'ajout d\'un lien compétence-effet: ' . $e->getMessage());
            
            return $this->json([
                'success' => false, 
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/{id}/edit', name: 'edit_skill_effect_link', methods: ['PUT', 'PATCH'])]
    public function editLink(
        Request $request, 
        EntityManagerInterface $em, 
        LinkSkillEffectRepository $linkRepo,
        HeroSkillRepository $skillRepo,
        SkillEffectRepository $effectRepo,
        int $id
    ): JsonResponse
    {
        try {
            $link = $linkRepo->find($id);
            if (!$link) {
                return $this->json(['success' => false, 'error' => 'Lien compétence-effet introuvable'], 404);
            }

            $data = json_decode($request->getContent(), true);
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'error' => 'JSON invalide: ' . json_last_error_msg()
                ], 400);
            }

            // Mise à jour de la compétence si fournie
            if (isset($data['skillId'])) {
                $skill = $skillRepo->find($data['skillId']);
                if (!$skill) {
                    return $this->json([
                        'success' => false, 
                        'error' => 'Compétence introuvable (ID: ' . $data['skillId'] . ')'
                    ], 404);
                }
                $link->setSkill($skill);
            }
            
            // Mise à jour de l'effet si fourni
            if (isset($data['effectId'])) {
                $effect = $effectRepo->find($data['effectId']);
                if (!$effect) {
                    return $this->json([
                        'success' => false, 
                        'error' => 'Effet introuvable (ID: ' . $data['effectId'] . ')'
                    ], 404);
                }
                $link->setEffect($effect);
            }
            
            // Mise à jour des paramètres si fournis
            if (isset($data['duration'])) {
                $link->setDuration((int) $data['duration']);
            }
            
            if (isset($data['accuracy'])) {
                $link->setAccuracy((int) $data['accuracy']);
            }
            
            if (isset($data['value'])) {
                $link->setValue((float) $data['value']);
            }

            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Lien compétence-effet mis à jour avec succès',
                'link' => $this->formatLinkData($link)
            ]);
        } catch (\Exception $e) {
            error_log('Erreur lors de la mise à jour d\'un lien compétence-effet: ' . $e->getMessage());
            
            return $this->json([
                'success' => false, 
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/{id}/delete', name: 'delete_skill_effect_link', methods: ['DELETE'])]
    public function deleteLink(EntityManagerInterface $em, LinkSkillEffectRepository $linkRepo, int $id): JsonResponse
    {
        try {
            $link = $linkRepo->find($id);
            if (!$link) {
                return $this->json(['success' => false, 'error' => 'Lien compétence-effet introuvable'], 404);
            }

            $skillName = $link->getSkill()->getName();
            $effectName = $link->getEffect()->getName();
            
            $em->remove($link);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => "Lien supprimé entre \"$skillName\" et \"$effectName\""
            ]);
        } catch (\Exception $e) {
            error_log('Erreur lors de la suppression d\'un lien compétence-effet: ' . $e->getMessage());
            
            return $this->json([
                'success' => false, 
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Récupère tous les effets disponibles pour faciliter la création de liens
     */
    #[Route('/available/effects', name: 'get_available_effects', methods: ['GET'])]
    public function getAvailableEffects(SkillEffectRepository $effectRepo): JsonResponse
    {
        $effects = $effectRepo->findAll();
        $data = array_map(function($effect) {
            return [
                'id' => $effect->getId(),
                'name' => $effect->getName(),
                'description' => $effect->getDescription()
            ];
        }, $effects);
        
        return $this->json($data);
    }
    
    /**
     * Récupère toutes les compétences disponibles pour faciliter la création de liens
     */
    #[Route('/available/skills', name: 'get_available_skills', methods: ['GET'])]
    public function getAvailableSkills(HeroSkillRepository $skillRepo): JsonResponse
    {
        $skills = $skillRepo->findAll();
        $data = array_map(function($skill) {
            return [
                'id' => $skill->getId(),
                'name' => $skill->getName(),
                'heroId' => $skill->getHero()->getId(),
                'heroName' => $skill->getHero()->getName()
            ];
        }, $skills);
        
        return $this->json($data);
    }
    
    /**
     * Formatte les données d'un lien compétence-effet pour l'API
     */
    private function formatLinkData(LinkSkillEffect $link): array
    {
        return [
            'id' => $link->getId(),
            'skill' => [
                'id' => $link->getSkill()->getId(),
                'name' => $link->getSkill()->getName(),
                'heroId' => $link->getSkill()->getHero()->getId(),
                'heroName' => $link->getSkill()->getHero()->getName()
            ],
            'effect' => [
                'id' => $link->getEffect()->getId(),
                'name' => $link->getEffect()->getName(),
                'description' => $link->getEffect()->getDescription()
            ],
            'duration' => $link->getDuration(),
            'accuracy' => $link->getAccuracy(),
            'value' => $link->getValue(),
            'fullDescription' => $link->getFullDescription(),
            'createdAt' => $link->getCreatedAt()->format('Y-m-d H:i:s')
        ];
    }
}