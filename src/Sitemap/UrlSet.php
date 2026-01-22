<?php

/*
 * This file is part of fof/sitemap.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 */

namespace FoF\Sitemap\Sitemap;

use FoF\Sitemap\Exceptions\SetLimitReachedException;
use XMLWriter;

class UrlSet
{
    const AMOUNT_LIMIT = 50000;

    /**
     * @var Url[]
     */
    public $urls = [];

    public function add(Url $url)
    {
        if (count($this->urls) >= static::AMOUNT_LIMIT) {
            throw new SetLimitReachedException();
        }

        $this->urls[] = $url;
    }

    public function addUrl($location, $lastModified = null, $changeFrequency = null, $priority = null, $alternatives = null)
    {
        $this->add(new Url($location, $lastModified, $changeFrequency, $priority, $alternatives));
    }

    public function toXml(): string
    {
        $settings = resolve(\Flarum\Settings\SettingsRepositoryInterface::class);
        $includeChangefreq = $settings->get('fof-sitemap.include_changefreq') ?? true;
        $includePriority = $settings->get('fof-sitemap.include_priority') ?? true;

        $writer = new XMLWriter();
        $writer->openMemory();
        // Disable indentation to reduce memory overhead
        $writer->setIndent(false);

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $writer->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');

        foreach ($this->urls as $url) {
            $this->renderUrl($writer, $url, $includeChangefreq, $includePriority);
        }

        $writer->endElement(); // urlset
        $writer->endDocument();

        return $writer->outputMemory();
    }

    /**
     * Render a single URL entry as XML.
     * Separated for clarity and maintainability.
     */
    private function renderUrl(XMLWriter $writer, Url $url, bool $includeChangefreq, bool $includePriority): void
    {
        $writer->startElement('url');

        $writer->writeElement('loc', $url->location);

        // Alternative language links
        if ($url->alternatives) {
            foreach ($url->alternatives as $alt) {
                $writer->startElement('xhtml:link');
                $writer->writeAttribute('rel', 'alternate');
                $writer->writeAttribute('hreflang', $alt->hreflang);
                $writer->writeAttribute('href', $alt->href);
                $writer->endElement(); // xhtml:link
            }
        }

        // Last modification date
        if ($url->lastModified) {
            $writer->writeElement('lastmod', $url->lastModified->toW3cString());
        }

        // Change frequency (optional based on settings)
        if ($url->changeFrequency && $includeChangefreq) {
            $writer->writeElement('changefreq', $url->changeFrequency);
        }

        // Priority (optional based on settings)
        if ($url->priority && $includePriority) {
            $writer->writeElement('priority', (string) $url->priority);
        }

        $writer->endElement(); // url
    }
}
