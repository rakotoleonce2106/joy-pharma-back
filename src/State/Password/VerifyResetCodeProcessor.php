<?php

namespace App\State\Password;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Service\ResetPasswordService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class VerifyResetCodeProcessor implements ProcessorInterface
{
    public function __construct(
        private ResetPasswordService $resetPasswordService
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $resetRequest = $this->resetPasswordService->getResetCodeValid($data->email, $data->code);

        if (!$resetRequest || new \DateTime() > $resetRequest->getExpiresAt()) {
            throw new BadRequestHttpException('Invalid or expired code');
        }

        return [
            'valid' => true,
            'message' => 'Code is valid'
        ];
    }
}

