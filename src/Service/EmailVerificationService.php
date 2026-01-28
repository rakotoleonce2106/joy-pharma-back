<?php

namespace App\Service;

use App\Entity\User;
use App\Service\N8nService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EmailVerificationService
{
    private const CODE_LENGTH = 6;
    private const CODE_EXPIRY_MINUTES = 15;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private N8nService $n8nService,
        private LoggerInterface $logger
    ) {}

    /**
     * Génère un code de vérification à 6 chiffres
     */
    public function generateVerificationCode(): string
    {
        return str_pad((string) random_int(100000, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Envoie un email de vérification via n8n
     */
    public function sendVerificationEmail(User $user): bool
    {
        $code = $this->generateVerificationCode();
        $expiresAt = new \DateTimeImmutable('+' . self::CODE_EXPIRY_MINUTES . ' minutes');

        // Met à jour l'utilisateur avec le code et la date d'expiration
        $user->setEmailVerificationCode($code);
        $user->setEmailVerificationCodeExpiresAt($expiresAt);



        // Prépare le contenu HTML de l'email
        $htmlBody = $this->getVerificationEmailTemplate($user, $code);
        $textBody = $this->getVerificationEmailTextTemplate($user, $code);

        // Tentative d'envoi par Email
        $emailResult = $this->n8nService->sendEmail(
            $user->getEmail(),
            'Vérifiez votre adresse email - Joy Pharma',
            $htmlBody,
            $textBody
        );

        if ($emailResult) {
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    /**
     * Envoie un SMS de vérification via n8n
     */
    public function sendVerificationSMS(User $user, ?string $code = null): bool
    {
        if (!$user->getPhone()) {
            return false;
        }

        $code = $code ?? $user->getEmailVerificationCode();
        if (!$code) {
            $code = $this->generateVerificationCode();
            $user->setEmailVerificationCode($code);
            $user->setEmailVerificationCodeExpiresAt(new \DateTimeImmutable('+' . self::CODE_EXPIRY_MINUTES . ' minutes'));
            $this->entityManager->flush();
        }

        $message = "Votre code de vérification Joy Pharma est : {$code}. Valable 15 minutes.";
        
        return $this->n8nService->sendSMS($user->getPhone(), $message);
    }

    /**
     * Vérifie un code de vérification
     */
    public function verifyEmailCode(string $email, string $code): bool
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            throw new NotFoundHttpException('Utilisateur non trouvé');
        }

        if ($user->isEmailVerified()) {
            throw new BadRequestHttpException('L\'adresse email est déjà vérifiée');
        }

        if (!$user->getEmailVerificationCode()) {
            throw new BadRequestHttpException('Aucun code de vérification trouvé. Veuillez demander un nouveau code.');
        }

        // Vérifie si le code a expiré
        if ($user->getEmailVerificationCodeExpiresAt() < new \DateTimeImmutable()) {
            throw new BadRequestHttpException('Le code de vérification a expiré. Veuillez demander un nouveau code.');
        }

        // Vérifie le code
        if ($user->getEmailVerificationCode() !== $code) {
            throw new BadRequestHttpException('Code de vérification invalide');
        }

        // Marque l'email comme vérifié
        $user->setIsEmailVerified(true);
        $user->setEmailVerificationCode(null);
        $user->setEmailVerificationCodeExpiresAt(null);

        $this->entityManager->flush();

        $this->logger->info('Email vérifié avec succès', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail()
        ]);

        return true;
    }

    /**
     * Renvoie un email de vérification (avec un nouveau code)
     */
    public function resendVerificationEmail(string $email): bool
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            throw new NotFoundHttpException('Utilisateur non trouvé');
        }

        if ($user->isEmailVerified()) {
            throw new BadRequestHttpException('L\'adresse email est déjà vérifiée');
        }

        // Génère un nouveau code et envoie l'email
        return $this->sendVerificationEmail($user);
    }

    /**
     * Vérifie si un utilisateur a besoin de vérification email
     */
    public function needsEmailVerification(User $user): bool
    {
        return !$user->isEmailVerified();
    }

    /**
     * Génère le template HTML pour l'email de vérification
     */
    private function getVerificationEmailTemplate(User $user, string $code): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification d'adresse email - Joy Pharma</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .code { font-size: 24px; font-weight: bold; color: #007bff; text-align: center; margin: 20px 0; padding: 10px; background-color: #e9ecef; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Joy Pharma</h1>
            <h2>Vérification de votre adresse email</h2>
        </div>
        <div class="content">
            <p>Bonjour {$user->getFullName()},</p>

            <p>Merci de vous être inscrit sur Joy Pharma ! Pour finaliser votre inscription, veuillez vérifier votre adresse email en utilisant le code suivant :</p>

            <div class="code">{$code}</div>

            <p>Ce code est valable pendant 15 minutes. Si vous n'avez pas demandé ce code, vous pouvez ignorer cet email.</p>

            <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>

            <p>Cordialement,<br>L'équipe Joy Pharma</p>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Génère le template texte pour l'email de vérification
     */
    private function getVerificationEmailTextTemplate(User $user, string $code): string
    {
        return <<<TEXT
Joy Pharma - Vérification d'adresse email

Bonjour {$user->getFullName()},

Merci de vous être inscrit sur Joy Pharma ! Pour finaliser votre inscription, veuillez vérifier votre adresse email en utilisant le code suivant :

{$code}

Ce code est valable pendant 15 minutes. Si vous n'avez pas demandé ce code, vous pouvez ignorer cet email.

Si vous avez des questions, n'hésitez pas à nous contacter.

Cordialement,
L'équipe Joy Pharma

---
Cet email a été envoyé automatiquement. Merci de ne pas y répondre.
TEXT;
    }

    /**
     * Envoie un email de réinitialisation de mot de passe
     */
    public function sendPasswordResetEmail(string $email, string $code): bool
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        $htmlBody = $this->getPasswordResetEmailTemplate($code);
        $textBody = $this->getPasswordResetEmailTextTemplate($code);

        // Envoi Email
        $emailResult = $this->n8nService->sendEmail(
            $email,
            'Code de réinitialisation - Joy Pharma',
            $htmlBody,
            $textBody
        );

        // Envoi SMS si l'utilisateur existe et a un téléphone
        $smsResult = false;
        if ($user && $user->getPhone()) {
            $smsResult = $this->sendPasswordResetSMS($user, $code);
        }

        if ($emailResult || $smsResult) {
            $this->logger->info('Email/SMS de réinitialisation envoyé', [
                'email' => $email,
                'email_sent' => $emailResult,
                'sms_sent' => $smsResult
            ]);
            return true;
        }

        $this->logger->error('Échec de l\'envoi de la réinitialisation (Email et SMS)', [
            'email' => $email
        ]);

        return false;
    }

    /**
     * Envoie un SMS de réinitialisation de mot de passe
     */
    public function sendPasswordResetSMS(User $user, string $code): bool
    {
        if (!$user->getPhone()) {
            return false;
        }

        $message = "Code de réinitialisation Joy Pharma : {$code}. Ne le partagez pas.";
        
        return $this->n8nService->sendSMS($user->getPhone(), $message);
    }

    /**
     * Génère le template HTML pour l'email de réinitialisation de mot de passe
     */
    private function getPasswordResetEmailTemplate(string $code): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de mot de passe - Joy Pharma</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .code { font-size: 24px; font-weight: bold; color: #dc3545; text-align: center; margin: 20px 0; padding: 10px; background-color: #f8d7da; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Joy Pharma</h1>
            <h2>Réinitialisation de votre mot de passe</h2>
        </div>
        <div class="content">
            <p>Bonjour,</p>

            <p>Vous avez demandé la réinitialisation de votre mot de passe Joy Pharma. Utilisez le code suivant pour réinitialiser votre mot de passe :</p>

            <div class="code">{$code}</div>

            <div class="warning">
                <strong>⚠️ Sécurité :</strong> Ce code expire dans 1 heure. Ne partagez jamais ce code avec qui que ce soit.
            </div>

            <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email. Votre mot de passe ne sera pas modifié.</p>

            <p>Pour des raisons de sécurité, vous ne pouvez utiliser ce code qu'une seule fois.</p>

            <p>Cordialement,<br>L'équipe Joy Pharma</p>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Génère le template texte pour l'email de réinitialisation de mot de passe
     */
    private function getPasswordResetEmailTextTemplate(string $code): string
    {
        return <<<TEXT
Joy Pharma - Réinitialisation de mot de passe

Bonjour,

Vous avez demandé la réinitialisation de votre mot de passe Joy Pharma. Utilisez le code suivant pour réinitialiser votre mot de passe :

{$code}

⚠️ Sécurité : Ce code expire dans 1 heure. Ne partagez jamais ce code avec qui que ce soit.

Si vous n'avez pas demandé cette réinitialisation, ignorez cet email. Votre mot de passe ne sera pas modifié.

Pour des raisons de sécurité, vous ne pouvez utiliser ce code qu'une seule fois.

Cordialement,
L'équipe Joy Pharma

---
Cet email a été envoyé automatiquement. Merci de ne pas y répondre.
TEXT;
    }
}