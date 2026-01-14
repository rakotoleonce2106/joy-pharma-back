<?php

namespace App\State\Password;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Service\EmailVerificationService;
use App\Service\ResetPasswordService;
use App\Service\UserService;

class SendResetEmailProcessor implements ProcessorInterface
{
    public function __construct(
        private UserService $userService,
        private ResetPasswordService $resetPasswordService,
        private EmailVerificationService $emailVerificationService
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->userService->getUserByEmail($data->email);
        
        if (!$user) {
            // Return same message for security (don't reveal if email exists)
            return [
                'success' => true,
                'message' => 'If an account exists with this email, you will receive a password reset code.'
            ];
        }
        
        // Invalidate any existing reset requests for this email
        $existingRequest = $this->resetPasswordService->getResetValid($data->email);
        if ($existingRequest) {
            $this->resetPasswordService->invalidateResetRequest($existingRequest);
        }
        
        $code = random_int(100000, 999999); // Generate a 6-digit code
        $this->resetPasswordService->createResetPassword($data->email, (string)$code);

        // Send email via n8n
        $this->emailVerificationService->sendPasswordResetEmail($data->email, (string)$code);

        return [
            'success' => true,
            'message' => 'If an account exists with this email, you will receive a password reset code.'
        ];
    }
}

