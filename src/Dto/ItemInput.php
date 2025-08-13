<?php


namespace App\Dto;


use Symfony\Component\Validator\Constraints as Assert;

class ItemInput
{
    #[Assert\NotBlank]
    public ?int $id = null;

    #[Assert\NotBlank]
    public ?int $quantity = null;
}