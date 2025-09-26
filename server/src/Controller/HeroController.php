<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Hero;

final class HeroController extends AbstractController
{
    #[Route('/heroes', name: 'get_heroes', methods: ['GET', 'OPTIONS'])]
    public function getHeroes(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200);
        }

        $heroes = $em->getRepository(Hero::class)->findAll();
        $heroData = array_map(function ($hero) {
            return [
                'id' => $hero->getId(),
                'name' => $hero->getName(),
                'HP' => $hero->getHP(),
                'DEF' => $hero->getDEF(),
                'ATK' => $hero->getATK(),
                'VIT' => $hero->getVIT(),
                'RES' => $hero->getRES(),
                'star' => $hero->getStar(),
                'type' => $hero->getType()
            ];
        }, $heroes);

        return new JsonResponse($heroData, 200);
    }

    #[Route('/heroes/{id}', name: 'get_hero', methods: ['GET', 'OPTIONS'], requirements: ['id' => '\d+'])]
    public function getHero(Request $request, EntityManagerInterface $em, int $id): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200);
        }

        $hero = $em->getRepository(Hero::class)->find($id);
        if (!$hero) {
            return new JsonResponse(['error' => 'Hero not found'], 404);
        }

        $heroData = [
            'id' => $hero->getId(),
            'name' => $hero->getName(),
            'HP' => $hero->getHP(),
            'DEF' => $hero->getDEF(),
            'ATK' => $hero->getATK(),
            'VIT' => $hero->getVIT(),
            'RES' => $hero->getRES(),
            'star' => $hero->getStar(),
            'type' => $hero->getType()
        ];

        return new JsonResponse($heroData, 200, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }

    #[Route('/heroes/add', name: 'add_hero', methods: ['POST', 'OPTIONS'])]
    public function addHero(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        $data = json_decode($request->getContent(), true);
        $hero = new Hero();
        $hero->setName($data['name']);
        $hero->setHP($data['HP']);
        $hero->setDEF($data['DEF']);
        $hero->setATK($data['ATK']);
        $hero->setVIT($data['VIT']);
        $hero->setRES($data['RES']);
        $hero->setStar($data['star']);
        $hero->setType($data['type']);

        $em->persist($hero);
        $em->flush();

        return new JsonResponse(['success' => true, 'id' => $hero->getId()], 201, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }
}