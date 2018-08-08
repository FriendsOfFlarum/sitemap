<?php

namespace Flagrow\Sitemap\Sitemap;

use Carbon\Carbon;

class Url
{
    public $location;
    /**
     * @var Carbon
     */
    public $lastModified;
    public $changeFrequency;
    public $priority;

    public function __construct($location, $lastModified = null, $changeFrequency = null, $priority = null)
    {
        $this->location = $location;
        $this->lastModified = $lastModified;
        $this->changeFrequency = $changeFrequency;
        $this->priority = $priority;
    }
}
