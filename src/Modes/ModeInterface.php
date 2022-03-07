<?php

namespace FoF\Sitemap\Modes;

use FoF\Sitemap\Generator;

interface ModeInterface
{
    /**
     * Stores the sitemap.
     *
     * @param Generator $generator
     * @return void
     */
    public function store(Generator $generator): void;

    /**
     * The base URL where files are remotely stored. Leave null for local.
     *
     * @return string|null
     */
    public function url(): ?string;
}
