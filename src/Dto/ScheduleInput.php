<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ScheduleInput
{
    /**
     * @var ScheduleItemInput[]
     */
    #[Assert\Valid]
    public array $schedules = [];
}

class ScheduleItemInput
{
    #[Assert\NotBlank]
    #[Assert\Range(min: 0, max: 6)]
    public int $dayOfWeek;

    #[Assert\NotBlank]
    public string $startTime;

    #[Assert\NotBlank]
    public string $endTime;

    public bool $isActive = true;
}


