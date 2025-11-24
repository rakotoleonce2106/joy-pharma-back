<?php

namespace App\Model;

use App\Entity\Plan;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class PaymentIntent
{
    #[Groups(['payment_intent:create'])]
    #[Assert\NotBlank(groups: ['payment_intent:create'])]
    public ?String $reference = null;

    #[Groups(['payment_intent:create'])]
    #[Assert\Choice(choices: ['stripe', 'mvola', 'mpgs'], message: 'Choose a valid payment method.', groups: ['payment_intent:create'])]
    public string $paymentMethod = 'stripe';

    #[Groups(['payment_intent:create'])]
    #[Assert\Regex(pattern: '/^\+2613[43500003]\d{7}$/', message: 'Invalid phone number.', groups: ['payment_intent:create'])]
    public ?string $phoneNumber = null;
}