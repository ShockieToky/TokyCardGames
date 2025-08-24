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

final class UserController extends AbstractController
{
    #[Route('/user/register', name: 'user_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $pseudo = $data['pseudo'] ?? '';
        $password = $data['password'] ?? '';

        if (!$pseudo || !$password) {
            return $this->json(['error' => 'Pseudo et mot de passe requis'], 400);
        }

        if ($userRepo->findOneBy(['pseudo' => $pseudo])) {
            return $this->json(['error' => 'Pseudo déjà utilisé'], 409);
        }

        $user = new User();
        $user->setPseudo($pseudo);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $em->persist($user);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/user/login', name: 'user_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepo,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $pseudo = $data['pseudo'] ?? '';
        $password = $data['password'] ?? '';

        $user = $userRepo->findOneBy(['pseudo' => $pseudo]);
        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Identifiants invalides'], 401);
        }

        return $this->json(['success' => true, 'userId' => $user->getId()]);
    }
}