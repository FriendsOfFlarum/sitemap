<sitemap>
    <loc>{{ $sitemap->url }}/{{ $sitemap->path }}</loc>
    <lastmod>{{ $sitemap->lastModified->toW3cString() }}</lastmod>
</sitemap>
