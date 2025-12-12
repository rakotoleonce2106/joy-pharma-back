<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

readonly class ToastService
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function addToast(string $type, string $message, ?string $description = null, array $options = []): void
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        $toasts = [
            'type' => $type,
            'message' => $message,
            'description' => $description,
            'options' => $options,
        ];

        $session->getFlashBag()->add('toasts', $toasts);
    }

    public function getToasts(): array
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        return $session->getFlashBag()->get('toasts');
    }
}
