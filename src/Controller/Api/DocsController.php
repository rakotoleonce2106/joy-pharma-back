<?php

namespace App\Controller\Api;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to serve Swagger UI documentation at /api/docs
 * This overrides the default /api/docs JSON-LD endpoint to serve HTML with CSS
 */
class DocsController extends AbstractController
{
    public function __construct(
        private readonly OpenApiFactoryInterface $openApiFactory
    ) {
    }

    #[Route('/api/docs', name: 'api_docs', methods: ['GET'], priority: 10)]
    public function __invoke(Request $request): Response
    {
        // Get the OpenAPI spec
        $openApi = ($this->openApiFactory)([]);
        $spec = json_encode($openApi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        $baseUrl = $request->getSchemeAndHttpHost() . $request->getBasePath();
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Joy Pharma API Documentation</title>
    <link rel="stylesheet" type="text/css" href="/bundles/apiplatform/swagger-ui/swagger-ui.css" />
    <link rel="stylesheet" type="text/css" href="/bundles/apiplatform/style.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin: 0;
            background: #fafafa;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script id="swagger-data" type="application/json">{"spec":{$spec},"oauth":{"redirectUrl":"{$baseUrl}/bundles/apiplatform/swagger-ui/oauth2-redirect.html","clientId":null,"clientSecret":null},"shortName":"Joy Pharma API","persistAuthorization":false}</script>
    <script src="/bundles/apiplatform/swagger-ui/swagger-ui-bundle.js"></script>
    <script src="/bundles/apiplatform/swagger-ui/swagger-ui-standalone-preset.js"></script>
    <script src="/bundles/apiplatform/init-swagger-ui.js"></script>
</body>
</html>
HTML;

        return new Response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }
}

