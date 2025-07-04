<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\ResetPasswordInput;
use App\Service\ResetPasswordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

class CheckCodeResetPasswordController extends AbstractController
{
    public function __invoke( #[MapRequestPayload] ResetPasswordInput $input,  ResetPasswordService $passwordService,): JsonResponse
    {

        $resetRequest = $passwordService->getResetCodeValid($input->email, $input->code);

        if (!$resetRequest || new \DateTime() > $resetRequest->getExpiresAt()) {
            return new JsonResponse(['error' => 'Invalid or expired code'], 400);
        }

        return new JsonResponse(['message' => 'Code is valid']);
    }
}
