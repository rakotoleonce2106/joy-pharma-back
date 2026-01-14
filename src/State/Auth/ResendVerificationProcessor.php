<?php

namespace App\State\Auth;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ResendVerificationInput;
use App\Service\EmailVerificationService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ResendVerificationProcessor implements ProcessorInterface
{
    public function __construct(
        private EmailVerificationService $emailVerificationService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        try {
            // $data is the ResendVerificationInput DTO
            if (!$data instanceof ResendVerificationInput) {
                throw new BadRequestHttpException('Données invalides');
            }

            // Resend verification email
            $this->emailVerificationService->resendVerificationEmail($data->email);

            return [
                'success' => true,
                'message' => 'Un nouvel email de vérification a été envoyé à votre adresse email.',
                'email' => $data->email
            ];

        } catch (BadRequestHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage(), $e);
        }
    }
}