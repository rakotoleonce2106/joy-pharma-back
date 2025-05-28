<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;

use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;

class ChoiceExtensionRuntime implements RuntimeExtensionInterface
{
    /**
     * @param string $value
     * @param ChoiceView[]|ChoiceGroupView[] $choices
     * @return ChoiceView|null
     */
    public function doFilterChoice(string $value, array $choices): ?ChoiceView
    {
        $choices = $this->compileChoices($choices);
        $filtered = $this->filterChoicesByValue($choices, $value);
        return $filtered ? reset($filtered) : null;
    }

    /**
     * @param ChoiceView[]|ChoiceGroupView[] $choices
     * @return array
     */
    private function compileChoices(array $choices): array
    {
        return array_reduce($choices, function ($carry, $item) {
            return ($item instanceof ChoiceGroupView) ? array_merge($carry, $item->choices) : array_merge($carry, [$item]);
        }, []);
    }

    /**
     * @param ChoiceView[] $choices
     * @param string $value
     * @return array
     */
    private function filterChoicesByValue(array $choices, string $value): array
    {
        return array_filter($choices, function(ChoiceView $choice) use ($value) {
            return $choice->value === $value;
        });
    }
}