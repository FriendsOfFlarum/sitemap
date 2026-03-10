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

/**
 * Streams sitemap URL-set XML directly to a php://temp stream via XMLWriter.
 *
 * No URL objects are accumulated in memory. Each entry is written and flushed
 * immediately. The underlying stream can be passed directly to a deploy backend
 * (e.g. Flysystem) without ever materialising the full XML as a PHP string.
 *
 * Use {@see UrlSet::stream()} to obtain the rewound stream resource when done,
 * then pass it to {@see DeployInterface::storeSet()}.
 */
class UrlSet
{
    const AMOUNT_LIMIT = 50000;

    /**
     * How often (in URL count) the XMLWriter in-memory buffer is flushed to
     * the underlying php://temp stream. Lower = less peak memory, marginally
     * more write() calls.
     */
    private const FLUSH_INTERVAL = 500;

    private int $count = 0;

    /** @var resource */
    private $stream;

    private XMLWriter $writer;

    private bool $includeChangefreq;
    private bool $includePriority;

    public function __construct(bool $includeChangefreq = true, bool $includePriority = true)
    {
        $this->includeChangefreq = $includeChangefreq;
        $this->includePriority = $includePriority;

        // php://temp: uses memory (up to 2 MB) then transparently spills to a
        // system temp file. No path to manage; PHP cleans it up on fclose().
        $stream = fopen('php://temp', 'r+b');

        if ($stream === false) {
            throw new \RuntimeException('Failed to open php://temp stream for sitemap UrlSet');
        }

        $this->stream = $stream;

        $this->writer = new XMLWriter();
        $this->writer->openMemory();
        $this->writer->setIndent(false);

        $this->writer->startDocument('1.0', 'UTF-8');
        $this->writer->startElement('urlset');
        $this->writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->writer->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');

        // Flush the document/urlset preamble to the stream immediately so
        // the writer's in-memory buffer starts empty for URL entries.
        fwrite($this->stream, $this->writer->flush(true));
    }

    /**
     * Write a URL entry directly to the stream.
     *
     * @throws SetLimitReachedException when the 50 000-URL limit is reached
     */
    public function add(Url $url): void
    {
        if ($this->count >= static::AMOUNT_LIMIT) {
            throw new SetLimitReachedException();
        }

        $this->writeUrl($url);
        $this->count++;

        // Periodically drain the XMLWriter in-memory buffer to the stream.
        if ($this->count % self::FLUSH_INTERVAL === 0) {
            fwrite($this->stream, $this->writer->flush(true));
        }
    }

    public function addUrl($location, $lastModified = null, $changeFrequency = null, $priority = null, $alternatives = null): void
    {
        $this->add(new Url($location, $lastModified, $changeFrequency, $priority, $alternatives));
    }

    public function count(): int
    {
        return $this->count;
    }

    /**
     * Finalise the XML document and return a rewound readable stream.
     *
     * The caller is responsible for closing the stream after use (i.e. after
     * passing it to {@see DeployInterface::storeSet()}).
     *
     * @return resource
     */
    public function stream()
    {
        $this->writer->endElement(); // urlset
        $this->writer->endDocument();
        fwrite($this->stream, $this->writer->flush(true));

        rewind($this->stream);

        return $this->stream;
    }

    private function writeUrl(Url $url): void
    {
        $this->writer->startElement('url');

        $this->writer->writeElement('loc', $url->location);

        if ($url->alternatives) {
            foreach ($url->alternatives as $alt) {
                $this->writer->startElement('xhtml:link');
                $this->writer->writeAttribute('rel', 'alternate');
                $this->writer->writeAttribute('hreflang', $alt->hreflang);
                $this->writer->writeAttribute('href', $alt->href);
                $this->writer->endElement();
            }
        }

        if ($url->lastModified) {
            $this->writer->writeElement('lastmod', $url->lastModified->toW3cString());
        }

        if ($url->changeFrequency && $this->includeChangefreq) {
            $this->writer->writeElement('changefreq', $url->changeFrequency);
        }

        if ($url->priority && $this->includePriority) {
            $this->writer->writeElement('priority', (string) $url->priority);
        }

        $this->writer->endElement(); // url
    }
}
