<?php

namespace App\Controller;

use App\Entity\Code;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class CodeController extends AbstractController
{

    #[Route('/code/create', name: 'create_code', methods: ['POST', 'OPTIONS'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $data = json_decode($request->getContent(), true);

        $name = $data['name'] ?? null;
        $expiration = $data['expirationDate'] ?? null;
        $scrollId = $data['scrollId'] ?? null;
        $scrollCount = $data['scrollCount'] ?? null;

        if (!$name || !$expiration || !$scrollId || !$scrollCount) {
            return new JsonResponse(['success' => false, 'error' => 'Champs manquants'], 400, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        try {
            $code = new Code();
            $code->setName($name);
            $code->setExpirationDate(new \DateTime($expiration));
            $code->setScrollId((int)$scrollId);
            $code->setScrollCount((int)$scrollCount);

            $em->persist($code);
            $em->flush();

            return new JsonResponse(['success' => true, 'id' => $code->getId()], 201, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }
    }
}