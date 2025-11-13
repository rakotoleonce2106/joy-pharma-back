<?php

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;

class BatchDeleteInput
{
    /**
     * @var array<int>
     */
    #[Assert\NotBlank]
    #[Assert\Count(min: 1)]
    public array $ids = [];
}

