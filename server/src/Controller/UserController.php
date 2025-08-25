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

            if (!$pseudo || !$password) {
                return new JsonResponse(['error' => 'Pseudo et mot de passe requis'], 400, [
                    'Access-Control-Allow-Origin' => 'http://localhost:3000',
                    'Access-Control-Allow-Credentials' => 'true'
                ]);
            }

            if ($userRepo->findOneBy(['pseudo' => $pseudo])) {
                return new JsonResponse(['error' => 'Pseudo déjà utilisé'], 409, [
                    'Access-Control-Allow-Origin' => 'http://localhost:3000',
                    'Access-Control-Allow-Credentials' => 'true'
                ]);
            }

            $user = new User();
            $user->setPseudo($pseudo);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setIsAdmin(false);

            $em->persist($user);
            $em->flush();

            return new JsonResponse(['success' => true, 'userId' => $user->getId()], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }
    }
    #[Route('/user/login', name: 'user_login', methods: ['POST', 'OPTIONS'])]
    public function login(
        Request $request,
        UserRepository $userRepo,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        try {
            $data = json_decode($request->getContent(), true);
            file_put_contents(sys_get_temp_dir() . '/login_debug.log', print_r($data, true) . "\n", FILE_APPEND);
            $pseudo = $data['pseudo'] ?? '';
            $password = $data['password'] ?? '';

            $user = $userRepo->findOneBy(['pseudo' => $pseudo]);
            if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
                return new JsonResponse(['error' => 'Identifiants invalides'], 401, [
                    'Access-Control-Allow-Origin' => 'http://localhost:3000',
                    'Access-Control-Allow-Credentials' => 'true'
                ]);
            }
            
            $request->getSession()->set('user_id', $user->getId());

            return new JsonResponse(['success' => true, 'userId' => $user->getId()], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }
    }
    #[Route('/user/me', name: 'user_me', methods: ['GET', 'OPTIONS'])]
    public function me(Request $request, UserRepository $userRepo): JsonResponse
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        if (!$userId) {
            return new JsonResponse(['error' => 'Non connecté'], 401, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur introuvable'], 404, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        return new JsonResponse([
            'success' => true,
            'userId' => $user->getId(),
            'pseudo' => $user->getPseudo()
        ], 200, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }
}