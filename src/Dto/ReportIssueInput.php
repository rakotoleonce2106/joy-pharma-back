<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ReportIssueInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['damaged_product', 'wrong_address', 'customer_unavailable', 'other'])]
    public string $type;

    #[Assert\NotBlank]
    public string $description;
}


