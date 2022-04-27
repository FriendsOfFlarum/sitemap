@php
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
@endphp
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach($sitemap->sets as $set)
        <sitemap>
            <loc>{{ $set->url }}</loc>
            <lastmod>{{ $set->lastModifiedAt->toW3cString() }}</lastmod>
        </sitemap>
    @endforeach
</sitemapindex>
