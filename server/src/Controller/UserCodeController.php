<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Code;
use App\Entity\UserCode;
use App\Entity\UserScroll;
use App\Entity\Scroll;
use App\Repository\CodeRepository;
use App\Repository\UserRepository;
use App\Repository\UserCodeRepository;
use App\Repository\UserScrollRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class UserCodeController extends AbstractController
{
    #[Route('/user/code/claim', name: 'user_code_claim', methods: ['POST', 'OPTIONS'])]
    public function claim(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepo,
        CodeRepository $codeRepo,
        UserCodeRepository $userCodeRepo,
        UserScrollRepository $userScrollRepo
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $session = $request->getSession();
        $userId = $session->get('user_id');
        if (!$userId) {
            return new JsonResponse(['success' => false, 'error' => 'Non connecté'], 401, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $data = json_decode($request->getContent(), true);
        $codeName = $data['code'] ?? null;

        if (!$codeName) {
            return new JsonResponse(['success' => false, 'error' => 'Code manquant'], 400, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Utilisateur introuvable'], 404, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $code = $codeRepo->findOneBy(['name' => $codeName]);
        if (!$code) {
            return new JsonResponse(['success' => false, 'error' => 'Code invalide'], 404, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        // Vérifier expiration
        if ($code->getExpirationDate() < new \DateTime()) {
            return new JsonResponse(['success' => false, 'error' => 'Code expiré'], 400, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        // Vérifier si déjà utilisé par cet utilisateur
        $alreadyUsed = $userCodeRepo->findOneBy(['user' => $user, 'code' => $code]);
        if ($alreadyUsed) {
            return new JsonResponse(['success' => false, 'error' => 'Code déjà utilisé'], 400, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        // Ajouter les scrolls à l'utilisateur
        $scrollId = $code->getScrollId();
        $scrollCount = $code->getScrollCount();

        // Récupérer l'entité Scroll
        $scroll = $em->getRepository(Scroll::class)->find($scrollId);
        if (!$scroll) {
            return new JsonResponse(['success' => false, 'error' => 'Scroll inconnu'], 400, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        try {
            // Vérifier si l'utilisateur a déjà ce scroll
            $userScroll = $userScrollRepo->findOneBy(['user' => $user, 'scroll' => $scroll]);
            if ($userScroll) {
                $userScroll->setQuantity($userScroll->getQuantity() + $scrollCount);
            } else {
                $userScroll = new UserScroll();
                $userScroll->setUser($user);
                $userScroll->setScroll($scroll);
                $userScroll->setQuantity($scrollCount);
                $em->persist($userScroll);
            }

            // Marquer le code comme utilisé par cet utilisateur
            $userCode = new UserCode();
            $userCode->setUser($user);
            $userCode->setCode($code);
            $em->persist($userCode);

            $em->flush();

            return new JsonResponse(['success' => true, 'message' => 'Récompense récupérée !'], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }
    }
}