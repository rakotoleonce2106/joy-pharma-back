<?php

namespace App\Dto\Admin;

use DateTimeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class OrderInput
{
    #[Assert\NotBlank]
    public ?string $reference = null;

    #[Assert\NotBlank]
    public ?int $customer = null;

    #[Assert\NotBlank]
    public ?string $phone = null;

    #[Assert\NotBlank]
    public ?string $priority = null;

    #[Assert\NotBlank]
    public ?string $status = null;

    public ?string $address = null;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?DateTimeInterface $scheduledDate = null;

    public ?int $deliveryPerson = null;

    public ?string $notes = null;

    /**
     * @var array<OrderItemInput>
     */
    #[Assert\NotBlank]
    #[Assert\Count(min: 1)]
    public array $items = [];
}

