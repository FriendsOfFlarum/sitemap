<?php

echo '<?xml version="1.0" encoding="UTF-8"?>';

/* @var $urlset \Flagrow\Sitemap\Sitemap\UrlSet */

?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($urlset->urls as $url)
    @include('fof-sitemap::url', ['url' => $url])
@endforeach
</urlset>
