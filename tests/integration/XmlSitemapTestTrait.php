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

namespace FoF\Sitemap\Tests\integration;

trait XmlSitemapTestTrait
{
    private function parseXmlWithNamespace(string $xml): \DOMXPath
    {
        $dom = new \DOMDocument();
        $result = $dom->loadXML($xml);
        $this->assertTrue($result, 'XML should be well-formed');

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        return $xpath;
    }

    private function getSitemapUrls(string $sitemapIndexXml): array
    {
        $xpath = $this->parseXmlWithNamespace($sitemapIndexXml);
        $sitemaps = $xpath->query('//sm:sitemap/sm:loc');

        $urls = [];
        foreach ($sitemaps as $sitemap) {
            $urls[] = $sitemap->textContent;
        }

        return $urls;
    }

    private function getUrlsFromSitemap(string $sitemapXml): array
    {
        $xpath = $this->parseXmlWithNamespace($sitemapXml);
        $urlNodes = $xpath->query('//sm:url/sm:loc');

        $urls = [];
        foreach ($urlNodes as $urlNode) {
            $urls[] = $urlNode->textContent;
        }

        return $urls;
    }

    private function assertValidSitemapIndexXml(string $xml): void
    {
        // Check if XML is well-formed
        $dom = new \DOMDocument();
        $result = $dom->loadXML($xml);
        $this->assertTrue($result, 'XML should be well-formed');

        // Validate against official sitemap index schema
        $schemaPath = __DIR__.'/../fixtures/siteindex.xsd';
        libxml_use_internal_errors(true);
        $isValid = $dom->schemaValidate($schemaPath);
        if (!$isValid) {
            $errors = libxml_get_errors();
            $errorMessages = array_map(fn ($error) => trim($error->message), $errors);
            $this->fail('XML does not validate against sitemap index schema: '.implode(', ', $errorMessages));
        }
        $this->assertTrue($isValid, 'XML should validate against sitemap index schema');
        libxml_clear_errors();
    }

    private function assertValidSitemapXml(string $xml): void
    {
        // Check if XML is well-formed
        $dom = new \DOMDocument();
        $result = $dom->loadXML($xml);
        $this->assertTrue($result, 'XML should be well-formed');

        // Validate against official sitemap schema
        $schemaPath = __DIR__.'/../fixtures/sitemap.xsd';
        libxml_use_internal_errors(true);
        $isValid = $dom->schemaValidate($schemaPath);
        if (!$isValid) {
            $errors = libxml_get_errors();
            $errorMessages = array_map(fn ($error) => trim($error->message), $errors);
            $this->fail('XML does not validate against sitemap schema: '.implode(', ', $errorMessages));
        }
        $this->assertTrue($isValid, 'XML should validate against sitemap schema');
        libxml_clear_errors();
    }
}
