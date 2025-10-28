<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ApproveOrderItemSuggestionInput
{
    #[Assert\NotBlank(message: 'Order item ID is required')]
    public ?int $orderItemId = null;

    public ?string $adminNotes = null;
}

