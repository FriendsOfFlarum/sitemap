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

use Carbon\Carbon;
use FoF\Sitemap\Deploy\StoredSet;
use XMLWriter;

class Sitemap
{
    public function __construct(
        public array $sets,
        public Carbon $lastModified
    ) {
    }

    public function toXML(): string
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        // Disable indentation to reduce memory overhead
        $writer->setIndent(false);

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('sitemapindex');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($this->sets as $set) {
            $this->renderSitemapEntry($writer, $set);
        }

        $writer->endElement(); // sitemapindex
        $writer->endDocument();

        return $writer->outputMemory();
    }

    /**
     * Render a single sitemap entry as XML.
     * Separated for clarity and maintainability.
     */
    private function renderSitemapEntry(XMLWriter $writer, StoredSet $set): void
    {
        $writer->startElement('sitemap');

        $writer->writeElement('loc', $set->url);
        $writer->writeElement('lastmod', $set->lastModifiedAt->toW3cString());

        $writer->endElement(); // sitemap
    }
}
