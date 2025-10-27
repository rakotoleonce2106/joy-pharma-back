<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RatingInput
{
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 5)]
    public int $rating;

    public ?string $comment = null;
}

