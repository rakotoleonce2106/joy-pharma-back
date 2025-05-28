<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\ChoiceExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class ChoiceExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('filter_choice', [ChoiceExtensionRuntime::class, 'doFilterChoice']),
        ];
    }
}