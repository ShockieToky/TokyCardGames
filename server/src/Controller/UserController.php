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
            $user->setIsAdmin(false);

            $em->persist($user);
            $em->flush();

            return new JsonResponse(['success' => true, 'userId' => $user->getId()], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500, [
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
            return new JsonResponse([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000'
            ]);
        }
    }
}