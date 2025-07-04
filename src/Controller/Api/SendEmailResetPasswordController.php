<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\ResetPasswordInput;
use App\Service\ResetPasswordService;
use App\Service\UserService;
use Random\RandomException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class SendEmailResetPasswordController extends AbstractController
{

    /**
     * @throws TransportExceptionInterface
     * @throws RandomException
     */
    public function __invoke(
        #[MapRequestPayload] ResetPasswordInput $input,
        UserService $userService,
        ResetPasswordService $passwordService,
        MailerInterface $mailer
    ): JsonResponse
    {
        $user = $userService->getUserByEmail($input->email);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }
        $code = random_int(100000, 999999); // Generate a 6-digit code
        $passwordService->createResetPassword($input->email, (string)$code);

        // Send email
        $emailMessage = (new TemplatedEmail())
            ->from('joypharma@gmail.com')
            ->to($input->email)
            ->subject('Reset Your Password')
            ->text("Your reset code is: $code");
        $mailer->send($emailMessage);

        return new JsonResponse(['message' => 'Reset code sent']);
    }
}
