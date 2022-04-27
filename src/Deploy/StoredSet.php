<?php

namespace FoF\Sitemap\Deploy;

use Carbon\Carbon;

class StoredSet
{
    public function __construct(
        public string $url,
        public Carbon $lastModifiedAt
    )
    {}
}
