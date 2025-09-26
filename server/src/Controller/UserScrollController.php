<?php

namespace App\Controller;

use App\Entity\UserScroll;
use App\Entity\Scroll;
use App\Repository\UserScrollRepository;
use App\Repository\ScrollRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class UserScrollController extends AbstractController
{
    #[Route('/user/scrolls', name: 'get_user_scrolls', methods: ['GET', 'OPTIONS'])]
    public function getUserScrolls(Request $request, UserScrollRepository $userScrollRepo, UserRepository $userRepo): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200);
        }

        $session = $request->getSession();
        $userId = $session->get('user_id');
        if (!$userId) {
            return new JsonResponse(['error' => 'Non connecté'], 401);
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur introuvable'], 404);
        }

        $collection = $userScrollRepo->findBy(['user' => $user]);
        $data = array_map(fn($us) => [
            'scrollId' => $us->getScroll()->getId(),
            'scrollName' => $us->getScroll()->getName(),
            'quantity' => $us->getQuantity()
        ], $collection);

        return new JsonResponse($data, 200);
    }

    #[Route('/user/scrolls/add', name: 'add_user_scroll', methods: ['POST', 'OPTIONS'])]
    public function addUserScroll(Request $request, EntityManagerInterface $em, UserRepository $userRepo, ScrollRepository $scrollRepo, UserScrollRepository $userScrollRepo): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200);
        }

        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? $request->getSession()->get('user_id');
        if (!$userId) {
            return new JsonResponse(['error' => 'Non connecté'], 401);
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $scroll = $scrollRepo->find($data['scrollId'] ?? null);
        if (!$scroll) {
            return new JsonResponse(['error' => 'Parchemin introuvable'], 404);
        }

        $quantity = (int)($data['quantity'] ?? 1);
        $userScroll = $userScrollRepo->findOneBy(['user' => $user, 'scroll' => $scroll]);
        if ($userScroll) {
            $userScroll->setQuantity($userScroll->getQuantity() + $quantity);
        } else {
            $userScroll = new UserScroll();
            $userScroll->setUser($user);
            $userScroll->setScroll($scroll);
            $userScroll->setQuantity($quantity);
            $em->persist($userScroll);
        }
        $em->flush();

        return new JsonResponse(['success' => true], 200);
    }

    #[Route('/user/scrolls/remove', name: 'remove_user_scroll', methods: ['POST', 'OPTIONS'])]
    public function removeUserScroll(Request $request, EntityManagerInterface $em, UserRepository $userRepo, ScrollRepository $scrollRepo, UserScrollRepository $userScrollRepo): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        $session = $request->getSession();
        $userId = $session->get('user_id');
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

        $data = json_decode($request->getContent(), true);
        $scroll = $scrollRepo->find($data['scrollId'] ?? null);
        if (!$scroll) {
            return new JsonResponse(['error' => 'Parchemin introuvable'], 404, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $userScroll = $userScrollRepo->findOneBy(['user' => $user, 'scroll' => $scroll]);
        if (!$userScroll) {
            return new JsonResponse(['error' => 'Ce parchemin n\'est pas dans la collection'], 404, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $quantity = (int)($data['quantity'] ?? 1);
        if ($userScroll->getQuantity() > $quantity) {
            $userScroll->setQuantity($userScroll->getQuantity() - $quantity);
        } else {
            $em->remove($userScroll);
        }
        $em->flush();

        return new JsonResponse(['success' => true], 200, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }
}