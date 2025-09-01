<?php

namespace FoF\Sitemap\Sitemap;

class Alternative
{
    public function __construct(
        public string $hreflang,
        public string $href
    ) {
    }
}
