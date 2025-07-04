<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\ResetPasswordInput;
use App\Service\ResetPasswordService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

class ResetPasswordController extends AbstractController
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function __invoke(
       #[MapRequestPayload] ResetPasswordInput $input,
        ResetPasswordService $passwordService,
        UserService $userService,
    ): JsonResponse
    {

        $resetRequest = $passwordService->getResetValid($input->email);

        if (!$resetRequest) {
            return new JsonResponse(['message' => 'Invalid reset request'], 400);
        }

        $user = $this->userService->getUserByEmail($input->email);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        // Update the user's password
        $this->userService->hashPassword($user, $input->password);

        // Invalidate the reset request
        $passwordService->invalidateResetRequest($resetRequest);

        return new JsonResponse(['message' => 'Password reset successfully']);
    }
}
