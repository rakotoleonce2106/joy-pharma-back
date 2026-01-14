<?php

namespace App\State\Auth;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\VerifyEmailInput;
use App\Service\EmailVerificationService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class VerifyEmailProcessor implements ProcessorInterface
{
    public function __construct(
        private EmailVerificationService $emailVerificationService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        try {
            // $data is the VerifyEmailInput DTO
            if (!$data instanceof VerifyEmailInput) {
                throw new BadRequestHttpException('Données invalides');
            }

            // Verify the email code
            $this->emailVerificationService->verifyEmailCode($data->email, $data->code);

            return [
                'success' => true,
                'message' => 'Votre adresse email a été vérifiée avec succès. Vous pouvez maintenant vous connecter.',
                'email' => $data->email
            ];

        } catch (BadRequestHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Erreur lors de la vérification: ' . $e->getMessage(), $e);
        }
    }
}