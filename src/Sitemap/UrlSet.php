<?php

namespace Flagrow\Sitemap\Sitemap;

class UrlSet
{
    /**
     * @var Url[]
     */
    public $urls = [];

    public function add(Url $url)
    {
        $this->urls[] = $url;
    }

    public function addUrl($location, $lastModified = null, $changeFrequency = null, $priority = null)
    {
        $this->add(new Url($location, $lastModified, $changeFrequency, $priority));
    }
}
