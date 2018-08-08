<?php

echo '<?xml version="1.0" encoding="UTF-8"?>';

/* @var $urlset \Flagrow\Sitemap\Sitemap\UrlSet */

?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($urlset->urls as $url)
    <url>
        <loc>{{ $url->location }}</loc>
@if ($url->lastModified)
        <lastmod>{{ $url->lastModified->format('Y-m-d\TH:i:sP') }}</lastmod>
@endif
@if ($url->changeFrequency)
        <changefreq>{{ $url->changeFrequency }}</changefreq>
@endif
@if ($url->priority)
        <priority>{{ $url->priority }}</priority>
@endif
    </url>
@endforeach
</urlset>
