<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DocsRedirectController extends AbstractController
{
    /**
     * Redirige /api/docs vers /api (Swagger UI)
     * API Platform expose Swagger UI via l'entrypoint /api avec Accept: text/html
     */
    #[Route('/api/docs', name: 'api_docs_redirect', methods: ['GET'])]
    public function redirectToApi(Request $request): RedirectResponse
    {
        // Redirige vers /api qui affichera Swagger UI si Accept: text/html
        return $this->redirect('/api');
    }

    /**
     * Redirige /docs vers /api (Swagger UI)
     */
    #[Route('/docs', name: 'docs_redirect', methods: ['GET'])]
    public function redirectDocsToApi(Request $request): RedirectResponse
    {
        // Redirige vers /api qui affichera Swagger UI si Accept: text/html
        return $this->redirect('/api');
    }
}

