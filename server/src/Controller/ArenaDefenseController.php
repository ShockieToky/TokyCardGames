<?php

namespace App\Controller;

use App\Entity\ArenaDefense;
use App\Entity\UserCollection;
use App\Repository\ArenaDefenseRepository;
use App\Repository\UserCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('arena/defense')]
final class ArenaDefenseController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ArenaDefenseRepository $arenaDefenseRepository;
    private UserCollectionRepository $userCollectionRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ArenaDefenseRepository $arenaDefenseRepository,
        UserCollectionRepository $userCollectionRepository
    ) {
        $this->entityManager = $entityManager;
        $this->arenaDefenseRepository = $arenaDefenseRepository;
        $this->userCollectionRepository = $userCollectionRepository;
    }

    /**
     * Liste les défenses d'arène de l'utilisateur connecté
     */
    #[Route('', name: 'arena_defense_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function listDefenses(): JsonResponse
    {
        $user = $this->getUser();
        $defenses = $this->arenaDefenseRepository->findBy(['user' => $user]);
        
        $data = [];
        foreach ($defenses as $defense) {
            $heroesData = [];
            foreach ($defense->getHeroes() as $userCollection) {
                $hero = $userCollection->getHero();
                $heroesData[] = [
                    'id' => $hero->getId(),
                    'name' => $hero->getName(),
                    'star' => $hero->getStar(),
                    'type' => $hero->getType(),
                    'image' => $hero->getImage(),
                ];
            }
            
            $data[] = [
                'id' => $defense->getId(),
                'name' => $defense->getName(),
                'isActive' => $defense->isActive(),
                'heroes' => $heroesData,
                'createdAt' => $defense->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $defense->getUpdatedAt() ? $defense->getUpdatedAt()->format('Y-m-d H:i:s') : null,
            ];
        }
        
        return $this->json($data);
    }

    /**
     * Récupère une défense d'arène spécifique
     */
    #[Route('/{id}', name: 'arena_defense_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function showDefense(int $id): JsonResponse
    {
        $defense = $this->arenaDefenseRepository->find($id);
        
        if (!$defense) {
            return $this->json(['error' => 'Défense non trouvée'], 404);
        }
        
        // Vérifier que l'utilisateur est bien le propriétaire
        if ($defense->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Vous n\'avez pas accès à cette défense'], 403);
        }
        
        $heroesData = [];
        foreach ($defense->getHeroes() as $userCollection) {
            $hero = $userCollection->getHero();
            $heroesData[] = [
                'id' => $hero->getId(),
                'name' => $hero->getName(),
                'star' => $hero->getStar(),
                'type' => $hero->getType(),
                'image' => $hero->getImage(),
            ];
        }
        
        $data = [
            'id' => $defense->getId(),
            'name' => $defense->getName(),
            'isActive' => $defense->isActive(),
            'heroes' => $heroesData,
            'createdAt' => $defense->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $defense->getUpdatedAt() ? $defense->getUpdatedAt()->format('Y-m-d H:i:s') : null,
        ];
        
        return $this->json($data);
    }

    /**
     * Récupère la défense active de l'utilisateur
     */
    #[Route('/active', name: 'arena_defense_active', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getActiveDefense(): JsonResponse
    {
        $user = $this->getUser();
        $defense = $this->arenaDefenseRepository->findOneBy(['user' => $user, 'isActive' => true]);
        
        if (!$defense) {
            return $this->json(['error' => 'Aucune défense active'], 404);
        }
        
        $heroesData = [];
        foreach ($defense->getHeroes() as $userCollection) {
            $hero = $userCollection->getHero();
            $heroesData[] = [
                'id' => $hero->getId(),
                'name' => $hero->getName(),
                'star' => $hero->getStar(),
                'type' => $hero->getType(),
                'image' => $hero->getImage(),
            ];
        }
        
        $data = [
            'id' => $defense->getId(),
            'name' => $defense->getName(),
            'isActive' => true,
            'heroes' => $heroesData,
            'createdAt' => $defense->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $defense->getUpdatedAt() ? $defense->getUpdatedAt()->format('Y-m-d H:i:s') : null,
        ];
        
        return $this->json($data);
    }

    /**
     * Crée une nouvelle défense d'arène
     */
    #[Route('', name: 'arena_defense_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createDefense(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur n'a pas déjà atteint le maximum de défenses
        $defenseCount = $this->arenaDefenseRepository->count(['user' => $user]);
        if ($defenseCount >= 4) {
            return $this->json(['error' => 'Vous avez atteint la limite de 4 défenses'], 400);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['name']) || !isset($data['heroes']) || !is_array($data['heroes']) || count($data['heroes']) !== 4) {
            return $this->json(['error' => 'Données invalides. Veuillez fournir un nom et exactement 4 héros'], 400);
        }
        
        // Créer la défense
        $defense = new ArenaDefense();
        $defense->setName($data['name']);
        $defense->setUser($user);
        
        // Récupérer les héros demandés et vérifier qu'ils appartiennent à l'utilisateur
        foreach ($data['heroes'] as $heroId) {
            $userCollection = $this->userCollectionRepository->findOneBy([
                'user' => $user,
                'hero' => $heroId
            ]);
            
            if (!$userCollection) {
                return $this->json(['error' => "Le héros #$heroId n'appartient pas à votre collection"], 400);
            }
            
            $defense->addHero($userCollection);
        }
        
        // Définir comme active si c'est la première défense
        if ($defenseCount === 0) {
            $defense->setIsActive(true);
        } else {
            $defense->setIsActive(false);
        }
        
        $this->entityManager->persist($defense);
        $this->entityManager->flush();
        
        return $this->json([
            'id' => $defense->getId(),
            'name' => $defense->getName(),
            'isActive' => $defense->isActive(),
            'message' => 'Défense créée avec succès'
        ], 201);
    }

    /**
     * Met à jour une défense d'arène existante
     */
    #[Route('/{id}', name: 'arena_defense_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateDefense(int $id, Request $request): JsonResponse
    {
        $defense = $this->arenaDefenseRepository->find($id);
        
        if (!$defense) {
            return $this->json(['error' => 'Défense non trouvée'], 404);
        }
        
        // Vérifier que l'utilisateur est bien le propriétaire
        if ($defense->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Vous n\'avez pas accès à cette défense'], 403);
        }
        
        $data = json_decode($request->getContent(), true);
        
        // Mise à jour du nom si fourni
        if (isset($data['name'])) {
            $defense->setName($data['name']);
        }
        
        // Mise à jour des héros si fournis
        if (isset($data['heroes']) && is_array($data['heroes']) && count($data['heroes']) === 4) {
            // Supprimer les héros actuels
            foreach ($defense->getHeroes() as $hero) {
                $defense->removeHero($hero);
            }
            
            // Ajouter les nouveaux héros
            $user = $this->getUser();
            foreach ($data['heroes'] as $heroId) {
                $userCollection = $this->userCollectionRepository->findOneBy([
                    'user' => $user,
                    'hero' => $heroId
                ]);
                
                if (!$userCollection) {
                    return $this->json(['error' => "Le héros #$heroId n'appartient pas à votre collection"], 400);
                }
                
                $defense->addHero($userCollection);
            }
        }
        
        // Mettre à jour le timestamp
        $defense->updateTimestamp();
        
        $this->entityManager->flush();
        
        return $this->json([
            'id' => $defense->getId(),
            'name' => $defense->getName(),
            'isActive' => $defense->isActive(),
            'message' => 'Défense mise à jour avec succès'
        ]);
    }

    /**
     * Supprime une défense d'arène
     */
    #[Route('/{id}', name: 'arena_defense_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteDefense(int $id): JsonResponse
    {
        $defense = $this->arenaDefenseRepository->find($id);
        
        if (!$defense) {
            return $this->json(['error' => 'Défense non trouvée'], 404);
        }
        
        // Vérifier que l'utilisateur est bien le propriétaire
        if ($defense->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Vous n\'avez pas accès à cette défense'], 403);
        }
        
        // Si c'est la défense active et qu'il y a d'autres défenses, activer la première défense disponible
        if ($defense->isActive()) {
            $user = $this->getUser();
            $otherDefenses = $this->arenaDefenseRepository->findBy(
                ['user' => $user, 'isActive' => false],
                ['id' => 'ASC']
            );
            
            if (count($otherDefenses) > 0) {
                $otherDefenses[0]->setIsActive(true);
                $this->entityManager->persist($otherDefenses[0]);
            }
        }
        
        $this->entityManager->remove($defense);
        $this->entityManager->flush();
        
        return $this->json(['message' => 'Défense supprimée avec succès']);
    }

    /**
     * Active une défense spécifique et désactive les autres
     */
    #[Route('/{id}/activate', name: 'arena_defense_activate', methods: ['PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function activateDefense(int $id): JsonResponse
    {
        $defense = $this->arenaDefenseRepository->find($id);
        
        if (!$defense) {
            return $this->json(['error' => 'Défense non trouvée'], 404);
        }
        
        // Vérifier que l'utilisateur est bien le propriétaire
        $user = $this->getUser();
        if ($defense->getUser() !== $user) {
            return $this->json(['error' => 'Vous n\'avez pas accès à cette défense'], 403);
        }
        
        // Vérifier que la défense contient bien 4 héros
        if (!$defense->isValid()) {
            return $this->json(['error' => 'Cette défense ne contient pas 4 héros et ne peut pas être activée'], 400);
        }
        
        // Désactiver toutes les défenses de l'utilisateur
        $allDefenses = $this->arenaDefenseRepository->findBy(['user' => $user]);
        foreach ($allDefenses as $d) {
            $d->setIsActive(false);
        }
        
        // Activer la défense demandée
        $defense->setIsActive(true);
        $defense->updateTimestamp();
        
        $this->entityManager->flush();
        
        return $this->json([
            'id' => $defense->getId(),
            'name' => $defense->getName(),
            'isActive' => true,
            'message' => 'Défense activée avec succès'
        ]);
    }
}