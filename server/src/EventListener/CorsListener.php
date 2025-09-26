<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class CorsListener
{
    /**
     * Gère les requêtes OPTIONS (preflight)
     */
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        
        // Vérifier si c'est une requête preflight OPTIONS
        if ($request->getMethod() === 'OPTIONS') {
            $origin = $request->headers->get('Origin');
            
            if ($origin === 'http://localhost:3000') {
                $response = new JsonResponse(null, Response::HTTP_NO_CONTENT);
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
                $response->headers->set('Access-Control-Max-Age', '3600');
                $response->headers->set('Vary', 'Origin');
                
                $event->setResponse($response);
            }
        }
    }

    /**
     * Ajoute les headers CORS aux réponses normales
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        // Autoriser uniquement depuis le front local
        $origin = $request->headers->get('Origin');
        if ($origin === 'http://localhost:3000') {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Vary', 'Origin');
        }
    }
}