<?php

namespace FoF\Sitemap\Modes;

use FoF\Sitemap\Generator;

class Memory implements ModeInterface
{
    public function store(Generator $generator): void
    {
        // In memory doesn't store. It gets the index on request, see retrieveIndex.
    }

    public function url(): ?string
    {
        return null;
    }
}
