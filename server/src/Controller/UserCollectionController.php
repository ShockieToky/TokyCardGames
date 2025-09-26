<?php

namespace App\Controller;

use App\Entity\UserCollection;
use App\Entity\Hero;
use App\Entity\UserScroll;
use App\Repository\UserRepository;
use App\Repository\ScrollRepository;
use App\Repository\UserScrollRepository;
use App\Repository\ScrollRateRepository;
use App\Repository\HeroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class UserCollectionController extends AbstractController
{
    #[Route('/user/invoke', name: 'user_invoke_hero', methods: ['POST'])]
    public function invokeHero(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepo,
        ScrollRepository $scrollRepo,
        UserScrollRepository $userScrollRepo,
        ScrollRateRepository $scrollRateRepo,
        HeroRepository $heroRepo
    ): JsonResponse {

        $session = $request->getSession();
        $userId = $session->get('user_id');
        if (!$userId) {
            return new JsonResponse(['error' => 'Non connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $scrollId = $data['scrollId'] ?? null;
        $count = isset($data['count']) && is_numeric($data['count']) && $data['count'] > 0 ? (int)$data['count'] : 1;

        if (!$scrollId) {
            return new JsonResponse(['error' => 'Parchemin non spécifié'], 400);
        }

        $user = $userRepo->find($userId);
        $scroll = $scrollRepo->find($scrollId);
        if (!$user || !$scroll) {
            return new JsonResponse(['error' => 'Utilisateur ou parchemin introuvable'], 404, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        // Vérifie que l'utilisateur possède assez de parchemins
        $userScroll = $userScrollRepo->findOneBy(['user' => $user, 'scroll' => $scroll]);
        if (!$userScroll || $userScroll->getQuantity() < $count) {
            return new JsonResponse(['error' => 'Pas assez de parchemins'], 400, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        // Retire les parchemins
        $userScroll->setQuantity($userScroll->getQuantity() - $count);
        if ($userScroll->getQuantity() <= 0) {
            $em->remove($userScroll);
        }
        $em->flush();

        // Récupère les rates pour ce parchemin
        $rates = $scrollRateRepo->findBy(['scroll' => $scroll]);
        if (!$rates) {
            return new JsonResponse(['error' => 'Rates non définis pour ce parchemin'], 400, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        // Filtre les rates à 0
        $rates = array_filter($rates, fn($rate) => $rate->getRate() > 0);
        if (empty($rates)) {
            return new JsonResponse(['error' => 'Aucune étoile summonable pour ce parchemin'], 400, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $resultHeroes = [];

        for ($i = 0; $i < $count; $i++) {
            // Tirage au sort de l'étoile selon les rates > 0
            $rand = mt_rand() / mt_getrandmax();
            $acc = 0;
            $star = null;
            foreach ($rates as $rate) {
                $acc += $rate->getRate();
                if ($rand <= $acc) {
                    $star = $rate->getStar();
                    break;
                }
            }
            if ($star === null) {
                $star = end($rates)->getStar();
            }

            // Sélectionne un héros avec cette étoile
            $heroes = $heroRepo->findBy(['star' => $star]);
            if (!$heroes || count($heroes) === 0) {
                return new JsonResponse(['error' => 'Aucun héros avec cette étoile'], 400, [
                    'Access-Control-Allow-Origin' => 'http://localhost:3000',
                    'Access-Control-Allow-Credentials' => 'true'
                ]);
            }
            $hero = $heroes[array_rand($heroes)];

            // Ajoute le héros à la collection de l'utilisateur
            $userCollection = new UserCollection();
            $userCollection->setUser($user);
            $userCollection->setHero($hero);
            $em->persist($userCollection);

            $resultHeroes[] = [
                'heroId' => $hero->getId(),
                'heroName' => $hero->getName(),
                'star' => $hero->getStar()
            ];
        }

        $em->flush();

        // Réponse adaptée pour x1 ou x10
        if ($count === 1) {
            $hero = $resultHeroes[0];
            return new JsonResponse([
                'success' => true,
                'heroId' => $hero['heroId'],
                'heroName' => $hero['heroName'],
                'star' => $hero['star']
            ], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        } else {
            return new JsonResponse([
                'success' => true,
                'heroes' => $resultHeroes
            ], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }
    }

    #[Route('/user/collection', name: 'get_user_collection', methods: ['GET', 'OPTIONS'])]
    public function getUserCollection(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
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

        $collection = $em->getRepository(UserCollection::class)->findBy(['user' => $user]);
        $data = array_map(function($uc) {
            return [
                'heroId' => $uc->getHero()->getId(),
                'heroName' => $uc->getHero()->getName(),
                'star' => $uc->getHero()->getStar()
            ];
        }, $collection);

        // Tri par étoiles décroissantes puis par ID croissant
        usort($data, function($a, $b) {
            if ($a['star'] === $b['star']) {
                return $a['heroId'] <=> $b['heroId'];
            }
            return $b['star'] <=> $a['star'];
        });

        return new JsonResponse($data);
    }
}
