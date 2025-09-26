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
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200);
        }

        try {
            $data = json_decode($request->getContent(), true);
            $pseudo = $data['pseudo'] ?? '';
            $password = $data['password'] ?? '';

            if (!$pseudo || !$password) {
                return new JsonResponse(['error' => 'Pseudo et mot de passe requis'], 400);
            }
            if ($userRepo->findOneBy(['pseudo' => $pseudo])) {
                return new JsonResponse(['error' => 'Pseudo déjà utilisé'], 409);
            }

            $user = new User();
            $user->setPseudo($pseudo);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setIsAdmin(false);

            $em->persist($user);
            $em->flush();

            // Créer une session automatique après inscription
            $session = $request->getSession();
            $session->set('user_id', $user->getId());
            $session->set('is_admin', $user->isAdmin());
            $session->save(); // Force la sauvegarde immédiate de la session

            return new JsonResponse(['success' => true, 'userId' => $user->getId()], 200);
        } catch (\Exception $e) {
            error_log('Erreur inscription: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/user/login', name: 'user_login', methods: ['POST', 'OPTIONS'])]
    public function login(
        Request $request,
        UserRepository $userRepo,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200);
        }

        try {
            $data = json_decode($request->getContent(), true);
            $pseudo = $data['pseudo'] ?? '';
            $password = $data['password'] ?? '';

            $user = $userRepo->findOneBy(['pseudo' => $pseudo]);
            if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
                return new JsonResponse(['error' => 'Identifiants invalides'], 401);
            }
            
            // Initialisation et sauvegarde de la session
            $session = $request->getSession();
            $session->set('user_id', $user->getId());
            $session->set('is_admin', $user->isAdmin()); // Stockage du statut admin
            $session->save(); // Force la sauvegarde immédiate de la session
            
            // Log de débogage
            error_log('Login réussi - Session ID: ' . $session->getId());
            error_log('ID utilisateur en session: ' . $user->getId());
            error_log('Admin status: ' . ($user->isAdmin() ? 'Oui' : 'Non'));

            return new JsonResponse([
                'success' => true, 
                'userId' => $user->getId(),
                'isAdmin' => $user->isAdmin()
            ], 200);
        } catch (\Exception $e) {
            error_log('Erreur login: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/user/me', name: 'user_me', methods: ['GET', 'OPTIONS'])]
    public function me(Request $request, UserRepository $userRepo): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200);
        }

        // Récupération et debug de la session
        $session = $request->getSession();
        $userId = $session->get('user_id');
        
        // Logs de débogage
        error_log('Me check - Session ID: ' . $session->getId());
        error_log('ID utilisateur en session: ' . ($userId ? $userId : 'NULL'));

        if (!$userId) {
            return new JsonResponse(['success' => false, 'error' => 'Non connecté'], 401);
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            // Nettoyer la session invalide
            $session->remove('user_id');
            $session->save();
            return new JsonResponse(['success' => false, 'error' => 'Utilisateur introuvable'], 404);
        }

        return new JsonResponse([
            'success' => true,
            'userId' => $user->getId(),
            'pseudo' => $user->getPseudo(),
            'isAdmin' => $user->isAdmin()
        ], 200);
    }

    #[Route('/users', name: 'get_users', methods: ['GET', 'OPTIONS'])]
    public function getUsers(Request $request, UserRepository $userRepo): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200);
        }

        $users = $userRepo->findAll();
        $data = array_map(fn($u) => [
            'id' => $u->getId(),
            'pseudo' => $u->getPseudo()
        ], $users);

        return new JsonResponse($data, 200);
    }

    #[Route('/logout', name: 'user_logout', methods: ['POST', 'OPTIONS'])]
    public function logout(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200);
        }

        // Récupérer la session et la vider
        $session = $request->getSession();
        $userId = $session->get('user_id');
        
        // Log de débogage
        error_log('Logout - Session ID: ' . $session->getId());
        error_log('ID utilisateur déconnecté: ' . ($userId ? $userId : 'NULL'));
        
        $session->remove('user_id');
        $session->remove('is_admin');
        $session->invalidate();
        $session->migrate(true); // Crée une nouvelle session avec un nouvel ID

        return new JsonResponse(['success' => true, 'message' => 'Déconnecté avec succès'], 200);
    }

    #[Route('/user/password/update', name: 'user_password_update', methods: ['POST', 'OPTIONS'])]
    public function updatePassword(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200);
        }

        // Récupérer la session
        $session = $request->getSession();
        $userId = $session->get('user_id');

        // Vérifier si un utilisateur est connecté
        if (!$userId) {
            return new JsonResponse(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        // Récupérer les données du formulaire
        $data = json_decode($request->getContent(), true);
        $oldPassword = $data['oldPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        // Vérifier que les données nécessaires sont présentes
        if (empty($oldPassword) || empty($newPassword)) {
            return new JsonResponse(['success' => false, 'error' => 'Ancien et nouveau mot de passe requis'], 400);
        }

        // Récupérer l'utilisateur
        $user = $userRepository->find($userId);
        if (!$user) {
            // Nettoyer la session invalide
            $session->remove('user_id');
            $session->save();
            return new JsonResponse(['success' => false, 'error' => 'Utilisateur non trouvé'], 404);
        }

        // Vérifier l'ancien mot de passe
        if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
            return new JsonResponse(['success' => false, 'error' => 'Ancien mot de passe incorrect'], 400);
        }

        try {
            // Mettre à jour le mot de passe
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            
            // Enregistrer les modifications
            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse(['success' => true], 200);
        } catch (\Exception $e) {
            error_log('Erreur mise à jour mot de passe: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false, 
                'error' => 'Erreur lors de la mise à jour du mot de passe'
            ], 500);
        }
    }
}