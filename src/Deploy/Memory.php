<?php

namespace FoF\Sitemap\Deploy;

use FoF\Sitemap\Generate\Generator;

class Memory implements DeployInterface
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
