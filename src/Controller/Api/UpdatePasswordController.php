<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\UpdatePasswordInput;
use App\Entity\User;
use App\Service\UserService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class UpdatePasswordController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(
        #[MapRequestPayload] UpdatePasswordInput $input
    ): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'User not authenticated'], 401);
        }

        // Verify current password
        if (!$this->passwordHasher->isPasswordValid($user, $input->currentPassword)) {
            return new JsonResponse(['message' => 'Current password is incorrect'], 400);
        }

        // Update password
        $this->userService->hashPassword($user, $input->newPassword);
        $this->userService->saveUser($user);

        // Send confirmation email
        try {
            $emailMessage = (new TemplatedEmail())
                ->from('noreply@joypharma.com')
                ->to($user->getEmail())
                ->subject('Password Changed - Joy Pharma')
                ->htmlTemplate('emails/password_changed.html.twig');
            $this->mailer->send($emailMessage);
        } catch (TransportExceptionInterface $e) {
            // Log error but don't fail the password change
            // You might want to log this: $logger->error('Failed to send password change email', ['error' => $e->getMessage()]);
        }

        return new JsonResponse(['message' => 'Password updated successfully'], 200);
    }
}

