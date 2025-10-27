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
        // Verify the code is valid
        $resetRequest = $passwordService->getResetCodeValid($input->email, $input->code);

        if (!$resetRequest) {
            return new JsonResponse(['message' => 'Invalid or expired reset code'], 400);
        }

        // Check if the reset code has expired
        if (new \DateTime() > $resetRequest->getExpiresAt()) {
            $passwordService->invalidateResetRequest($resetRequest);
            return new JsonResponse(['message' => 'Reset code has expired'], 400);
        }

        $user = $this->userService->getUserByEmail($input->email);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        // Update the user's password
        $this->userService->hashPassword($user, $input->password);
        $this->userService->saveUser($user);

        // Invalidate the reset request
        $passwordService->invalidateResetRequest($resetRequest);

        return new JsonResponse(['message' => 'Password reset successfully']);
    }
}
