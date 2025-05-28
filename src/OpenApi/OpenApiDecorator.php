<?php

declare(strict_types=1);

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Model\Server;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class OpenApiDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private RequestStack $requestStack,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $openApi = $this->addServerConfiguration($openApi);
        return $this->addUniqueTags($openApi);
    }

    private function addServerConfiguration(OpenApi $openApi): OpenApi
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return $openApi;
        }

        $baseUrl = $request->getSchemeAndHttpHost() . $request->getBasePath();
        $server = new Server($baseUrl, 'Current server');

        return $openApi->withServers([$server]);
    }

    private function addUniqueTags(OpenApi $openApi): OpenApi
    {
        $paths = $openApi->getPaths();
        $tags = [];

        foreach ($paths->getPaths() as $pathItem) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                $operationMethod = 'get' . ucfirst($method);
                $operation = $pathItem->{$operationMethod}();
                if (!$operation) {
                    continue;
                }
                $tags = [...$tags, ...$operation->getTags() ?? []];
            }
        }

        $uniqueTags = array_values(array_unique($tags));
        return $openApi->withTags(array_map(fn(string $tag): array => ['name' => $tag], $uniqueTags));
    }
}
