<url>
    <loc>{!! htmlspecialchars($url->location, ENT_XML1) !!}</loc>
    @if ($url->lastModified)
        <lastmod>{!! $url->lastModified->toW3cString() !!}</lastmod>
    @endif
    @if ($url->changeFrequency)
        <changefreq>{!! htmlspecialchars($url->changeFrequency, ENT_XML1) !!}</changefreq>
    @endif
    @if ($url->priority)
        <priority>{!! htmlspecialchars($url->priority, ENT_XML1) !!}</priority>
    @endif
</url>
