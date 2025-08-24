<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    #[Route('/user/register', name: 'user_register', methods: ['POST', 'OPTIONS'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepo
    ): JsonResponse {
        // Pour les requêtes OPTIONS (CORS preflight)
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }
        
        try {
            // Utilisation du dossier temporaire du système
            $tempDir = sys_get_temp_dir();
            file_put_contents($tempDir . '/symfony_debug.log', 
                            "Méthode: " . $request->getMethod() . "\n" . 
                            "Contenu: " . $request->getContent() . "\n", 
                            FILE_APPEND);
            
            $data = json_decode($request->getContent(), true);
            
            // Log les données décodées également dans temp
            file_put_contents($tempDir . '/symfony_data.log', 
                            "Données: " . print_r($data, true), 
                            FILE_APPEND);
                              
            $pseudo = $data['pseudo'] ?? '';
            $password = $data['password'] ?? '';

            if (!$pseudo || !$password) {
                return new JsonResponse(['error' => 'Pseudo et mot de passe requis'], 400, [
                    'Access-Control-Allow-Origin' => 'http://localhost:3000'
                ]);
            }

            if ($userRepo->findOneBy(['pseudo' => $pseudo])) {
                return new JsonResponse(['error' => 'Pseudo déjà utilisé'], 409, [
                    'Access-Control-Allow-Origin' => 'http://localhost:3000'
                ]);
            }

            $user = new User();
            $user->setPseudo($pseudo);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            
            // Log avant persistence
            file_put_contents($tempDir . '/symfony_before_persist.log', 
                            "User: " . $pseudo, 
                            FILE_APPEND);
            
            $em->persist($user);
            $em->flush();
            
            // Log après persistence
            file_put_contents($tempDir . '/symfony_after_persist.log', 
                            "User ID: " . $user->getId(), 
                            FILE_APPEND);

            return new JsonResponse(['success' => true, 'userId' => $user->getId()], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000'
            ]);
            
        } catch (\Exception $e) {
            // Log l'erreur complète dans le dossier temporaire
            $tempDir = sys_get_temp_dir();
            file_put_contents($tempDir . '/symfony_error.log', 
                            "Erreur: " . $e->getMessage() . "\n" .
                            "Trace: " . $e->getTraceAsString() . "\n", 
                            FILE_APPEND);
            
            return new JsonResponse(['error' => 'Erreur serveur: ' . $e->getMessage()], 500, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000'
            ]);
        }
    }

    #[Route('/user/login', name: 'user_login', methods: ['POST', 'OPTIONS'])]
    public function login(
        Request $request,
        UserRepository $userRepo,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        // Pour les requêtes OPTIONS (CORS preflight)
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        try {
            $data = json_decode($request->getContent(), true);
            $pseudo = $data['pseudo'] ?? '';
            $password = $data['password'] ?? '';

            $user = $userRepo->findOneBy(['pseudo' => $pseudo]);
            if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
                return new JsonResponse(['error' => 'Identifiants invalides'], 401, [
                    'Access-Control-Allow-Origin' => 'http://localhost:3000'
                ]);
            }

            return new JsonResponse(['success' => true, 'userId' => $user->getId()], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000'
            ]);
        } catch (\Exception $e) {
            $tempDir = sys_get_temp_dir();
            file_put_contents($tempDir . '/symfony_login_error.log', 
                            "Erreur: " . $e->getMessage() . "\n", 
                            FILE_APPEND);
            
            return new JsonResponse(['error' => 'Erreur serveur: ' . $e->getMessage()], 500, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000'
            ]);
        }
    }

    #[Route('/user/info/{id}', name: 'user_info', methods: ['GET'])]
    public function info(
        int $id,
        UserRepository $userRepo
    ): JsonResponse {
        try {
            $user = $userRepo->find($id);
            if (!$user) {
                return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404, [
                    'Access-Control-Allow-Origin' => 'http://localhost:3000'
                ]);
            }

            return new JsonResponse([
                'success' => true,
                'pseudo' => $user->getPseudo(),
                'isAdmin' => $user->isAdmin()
            ], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000'
            ]);
        } catch (\Exception $e) {
            $tempDir = sys_get_temp_dir();
            file_put_contents($tempDir . '/symfony_info_error.log', 
                            "Erreur: " . $e->getMessage() . "\n", 
                            FILE_APPEND);
            
            return new JsonResponse(['error' => 'Erreur serveur'], 500, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000'
            ]);
        }
    }
}