<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach($set->urls as $url)
    @include('fof-sitemap::url', ['url' => $url, 'settings' => $settings ?? null])
@endforeach
</urlset>
