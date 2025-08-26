<?php

namespace App\Controller;

use App\Entity\ScrollRate;
use App\Entity\Scroll;
use App\Repository\ScrollRateRepository;
use App\Repository\ScrollRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ScrollRateController extends AbstractController
{
    #[Route('/scroll/rate/{scrollId}', name: 'get_scroll_rates', methods: ['GET', 'OPTIONS'])]
    public function getRates(Request $request, ScrollRateRepository $rateRepo, ScrollRepository $scrollRepo, int $scrollId): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        $scroll = $scrollRepo->find($scrollId);
        if (!$scroll) {
            return new JsonResponse(['error' => 'Parchemin introuvable'], 404, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $rates = $rateRepo->findBy(['scroll' => $scroll]);
        $data = [];
        $total = 0;
        foreach ($rates as $rate) {
            $data[] = [
                'star' => $rate->getStar(),
                'rate' => $rate->getRate()
            ];
            $total += $rate->getRate();
        }

        return new JsonResponse([
            'scrollId' => $scrollId,
            'rates' => $data,
            'total' => $total // doit être <= 1
        ], 200, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }

    #[Route('/scroll/rate/{scrollId}/set', name: 'set_scroll_rates', methods: ['POST', 'OPTIONS'])]
    public function setRates(Request $request, EntityManagerInterface $em, ScrollRepository $scrollRepo, ScrollRateRepository $rateRepo, int $scrollId): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }

        $scroll = $scrollRepo->find($scrollId);
        if (!$scroll) {
            return new JsonResponse(['error' => 'Parchemin introuvable'], 404, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['rates']) || !is_array($data['rates'])) {
            return new JsonResponse(['error' => 'Format invalide'], 400, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        $total = 0;
        foreach ($data['rates'] as $rateData) {
            $total += $rateData['rate'];
        }
        if ($total > 1) {
            return new JsonResponse(['error' => 'Le total des rates dépasse 1'], 400, [
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true'
            ]);
        }

        // Supprime les anciens rates
        foreach ($rateRepo->findBy(['scroll' => $scroll]) as $oldRate) {
            $em->remove($oldRate);
        }

        // Ajoute les nouveaux rates
        foreach ($data['rates'] as $rateData) {
            $rate = new ScrollRate();
            $rate->setScroll($scroll);
            $rate->setStar($rateData['star']);
            $rate->setRate($rateData['rate']);
            $em->persist($rate);
        }
        $em->flush();

        return new JsonResponse(['success' => true], 200, [
            'Access-Control-Allow-Origin' => 'http://localhost:3000',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }
}