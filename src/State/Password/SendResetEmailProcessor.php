<?php

namespace App\State\Password;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Service\ResetPasswordService;
use App\Service\UserService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class SendResetEmailProcessor implements ProcessorInterface
{
    public function __construct(
        private UserService $userService,
        private ResetPasswordService $resetPasswordService,
        private MailerInterface $mailer
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

        // Send email with HTML template
        try {
            $emailMessage = (new TemplatedEmail())
                ->from('noreply@joypharma.com')
                ->to($data->email)
                ->subject('Password Reset Code - Joy Pharma')
                ->htmlTemplate('emails/reset_password.html.twig')
                ->context([
                    'code' => $code,
                ]);
            $this->mailer->send($emailMessage);
        } catch (\Exception $e) {
            // Log but don't reveal error to user
        }

        return [
            'success' => true,
            'message' => 'If an account exists with this email, you will receive a password reset code.'
        ];
    }
}

