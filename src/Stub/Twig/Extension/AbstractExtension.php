<?php

namespace Twig\Extension;

/**
 * Stub class for Twig\Extension\AbstractExtension
 * This is a minimal implementation to allow VichUploaderBundle to load
 * without requiring the full Twig package.
 */
abstract class AbstractExtension
{
    public function getName(): string
    {
        return static::class;
    }
}

