<?php

namespace App\Service;


use App\Entity\ResetPassword;
use App\Repository\ResetPasswordRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class ResetPasswordService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private ResetPasswordRepository $repository,
    ) {
    }

    public function createResetPassword(string $email,string $code): void
    {
        $resetRequest = new ResetPassword();

        $resetRequest->setEmail($email);
        $resetRequest->setCode($code);
        $resetRequest->setExpiresAt(new \DateTimeImmutable('+1 hour'));
        $resetRequest->setIsValid(true);
        $this->manager->persist($resetRequest);
        $this->manager->flush();
    }


    public function getResetValid(string $email): ?ResetPassword
    {
        return $this->repository->findOneBy(['email' => $email, 'isValid' => true]);
    }

    public function getResetCodeValid(string $email,string $code): ?ResetPassword
    {
        return $this->repository->findOneBy(['email' => $email, 'code' => $code, 'isValid' => true]);
    }

    // Invalidate the reset request
    public function invalidateResetRequest(ResetPassword $request): void
{
    $request->setIsValid(false);
    $this->manager->flush();
}



    public function updateResetPassword(ResetPassword $request): void
    {
        $this->manager->flush();
    }

    public function deleteResetPassword(ResetPassword $request): void
    {
        $this->manager->remove($request);
        $this->manager->flush();
    }

}
