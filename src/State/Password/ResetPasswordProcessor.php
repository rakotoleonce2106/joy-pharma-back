<?php

namespace App\State\Password;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Service\ResetPasswordService;
use App\Service\UserService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResetPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private UserService $userService,
        private ResetPasswordService $resetPasswordService
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        // Verify the code is valid
        $resetRequest = $this->resetPasswordService->getResetCodeValid($data->email, $data->code);

        if (!$resetRequest) {
            throw new BadRequestHttpException('Invalid or expired reset code');
        }

        // Check if the reset code has expired
        if (new \DateTime() > $resetRequest->getExpiresAt()) {
            $this->resetPasswordService->invalidateResetRequest($resetRequest);
            throw new BadRequestHttpException('Reset code has expired');
        }

        $user = $this->userService->getUserByEmail($data->email);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        // Update the user's password
        $this->userService->hashPassword($user, $data->password);
        $this->userService->saveUser($user);

        // Invalidate the reset request
        $this->resetPasswordService->invalidateResetRequest($resetRequest);

        return [
            'success' => true,
            'message' => 'Password reset successfully'
        ];
    }
}

