<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;

readonly class PaymentService
{

    public function __construct(
        private EntityManagerInterface $manager,
    )
    {
    }

    public function createPayment(Payment $payment): Payment
    {

        $this->manager->persist($payment);
        return $payment;

    }

    public function updatePaymentStatusByTransactionId(string $transactionId, PaymentStatus $newStatus): Payment
    {
        $payment = $this->manager->getRepository(Payment::class)->findOneBy(['transactionId' => $transactionId]);
        $payment->setStatus($newStatus);
        $this->manager->persist($payment);
        return $payment;
    }

    public function updatePayment(Payment $payment): Payment
    {
        $this->manager->persist($payment);
        return $payment;
    }
}
