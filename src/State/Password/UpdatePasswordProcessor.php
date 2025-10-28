<?php

namespace App\State\Password;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Service\UserService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UpdatePasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private UserService $userService,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('', 'User not authenticated');
        }

        // Verify current password
        if (!$this->passwordHasher->isPasswordValid($user, $data->currentPassword)) {
            throw new BadRequestHttpException('Current password is incorrect');
        }

        // Update password
        $this->userService->hashPassword($user, $data->newPassword);
        $this->userService->saveUser($user);

        // Send confirmation email
        try {
            $emailMessage = (new TemplatedEmail())
                ->from('noreply@joypharma.com')
                ->to($user->getEmail())
                ->subject('Password Changed - Joy Pharma')
                ->htmlTemplate('emails/password_changed.html.twig');
            $this->mailer->send($emailMessage);
        } catch (\Exception $e) {
            // Log error but don't fail the password change
        }

        return [
            'success' => true,
            'message' => 'Password updated successfully'
        ];
    }
}

