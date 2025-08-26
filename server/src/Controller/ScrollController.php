<?php

namespace App\Controller;

use App\Entity\Scroll;
use App\Repository\ScrollRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ScrollController extends AbstractController
{
    #[Route('/scrolls', name: 'get_scrolls', methods: ['GET', 'OPTIONS'])]
    public function getScrolls(Request $request, ScrollRepository $scrollRepo): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        $scrolls = $scrollRepo->findAll();
        $data = array_map(fn($scroll) => [
            'id' => $scroll->getId(),
            'name' => $scroll->getName(),
            'description' => $scroll->getDescription()
        ], $scrolls);

        return new JsonResponse($data, 200, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }

    #[Route('/scrolls/add', name: 'add_scrolls', methods: ['POST', 'OPTIONS'])]
    public function addScrolls(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['name'], $data['description'])) {
            return new JsonResponse(['error' => 'Champs manquants'], 400, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $scroll = new Scroll();
        $scroll->setName($data['name']);
        $scroll->setDescription($data['description']);

        $em->persist($scroll);
        $em->flush();

        return new JsonResponse(['success' => true, 'id' => $scroll->getId()], 201, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }

    #[Route('/scrolls/update/{id}', name: 'update_scrolls', methods: ['PUT', 'OPTIONS'])]
    public function updateScrolls(Request $request, EntityManagerInterface $em, ScrollRepository $scrollRepo, int $id): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'PUT, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        $scroll = $scrollRepo->find($id);
        if (!$scroll) {
            return new JsonResponse(['error' => 'Scroll introuvable'], 404, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['name'])) {
            $scroll->setName($data['name']);
        }
        if (isset($data['description'])) {
            $scroll->setDescription($data['description']);
        }

        $em->flush();

        return new JsonResponse(['success' => true], 200, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }
}