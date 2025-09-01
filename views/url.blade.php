<url>
    <loc>{!! htmlspecialchars($url->location, ENT_XML1) !!}</loc>
    @if ($url->alternatives)
    @foreach ($url->alternatives as $alt)
    <xhtml:link rel="alternate" hreflang="{!! htmlspecialchars($alt->hreflang, ENT_XML1) !!}" href="{!! htmlspecialchars($alt->href, ENT_XML1) !!}" />
    @endforeach
    @endif
    @if ($url->lastModified)
    <lastmod>{!! $url->lastModified->toW3cString() !!}</lastmod>
    @endif
    @if ($url->changeFrequency && ($settings?->get('fof-sitemap.include_changefreq') ?? true))
    <changefreq>{!! htmlspecialchars($url->changeFrequency, ENT_XML1) !!}</changefreq>
    @endif
    @if ($url->priority && ($settings?->get('fof-sitemap.include_priority') ?? true))
    <priority>{!! htmlspecialchars($url->priority, ENT_XML1) !!}</priority>
    @endif
</url>
