# SEO & Sitemap by FriendsOfFlarum
[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/FriendsOfFlarum/sitemap/blob/master/LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/fof/sitemap.svg)](https://packagist.org/packages/fof/sitemap) [![Total Downloads](https://img.shields.io/packagist/dt/fof/sitemap.svg)](https://packagist.org/packages/fof/sitemap) [![OpenCollective](https://img.shields.io/badge/opencollective-fof-blue.svg)](https://opencollective.com/fof/donate)

A comprehensive SEO solution for Flarum that provides both XML sitemaps and robots.txt generation to help search engines discover and index your forum content effectively.

## Features

- **XML Sitemaps**: Automatically generated sitemaps with intelligent content discovery
- **Robots.txt Generation**: Standards-compliant robots.txt with dynamic path detection
- **Search Engine Compliance**: Ensures proper indexing while protecting sensitive areas
- **Extensible Architecture**: Other extensions can easily customize both sitemaps and robots.txt
- **Performance Optimized**: Multiple generation modes for forums of all sizes
- **Smart Integration**: Automatically detects and includes content from popular extensions

The extension intelligently includes content like Discussions, Users, Tags (flarum/tags), and Pages (fof/pages) while providing extensive customization options for developers.

## Installation

### Requirements

- **PHP**: 8.0 or greater
- **Memory**: Minimum 128MB PHP memory limit. 256MB recommended for forums with 100k+ items.
- **Flarum**: Compatible with Flarum 1.3.1+

For very large forums (700k+ items across all resource types), 512MB is recommended when using cached multi-file mode with many extensions installed.

Install with composer:

```bash
composer require fof/sitemap
```

## Updating

```bash
composer update fof/sitemap
php flarum migrate
php flarum cache:clear
```

## XML Sitemap Generation

The extension automatically generates XML sitemaps at `/sitemap.xml` that help search engines discover and index your forum content.

### Generation Modes

There are two modes available, both serving content from your main domain for search engine compliance.

#### Runtime Mode

The sitemap is generated on-the-fly when requested. Individual sitemap files are served at `/sitemap-1.xml`, `/sitemap-2.xml`, etc.

**Best for**: Small to medium forums with less than **10,000 total items** (discussions, users, tags, pages combined). Most shared hosting environments.

#### Cached Multi-File Mode

Sitemaps are pre-generated and updated via the Flarum scheduler. Content is stored on your configured storage (local disk, S3, CDN) but always served from your main domain.

**Best for**: Larger forums starting at 10,000+ items.

**Storage recommendations**:
- **Local disk**: Simple setup, but requires queue workers to have write access to `public/sitemaps/` (see [Queue Workers section](#queue-workers-and-multi-server-deployments))
- **S3/R2/CDN** (recommended): Eliminates filesystem access issues, better for Docker/multi-server deployments, scales effortlessly

**Manual rebuild**:
```bash
php flarum fof:sitemap:build
```

#### Performance Optimizations

The extension includes several automatic optimizations:

- **Streaming XML generation** (v2.6.0+): Each URL is written directly to a `php://temp` stream as it is processed. The XMLWriter buffer is flushed every 500 entries. No full XML string is ever held in PHP RAM — the stream is passed directly to Flysystem's `put()`, resulting in near-zero overhead per set regardless of forum size.
- **Column pruning** (v2.6.0+, enabled by default): Fetches only the columns needed for URL and date generation (`id`, `slug`/`username`, dates) instead of `SELECT *`. Provides a ~7× reduction in per-model RAM for Discussion and User queries. Disable in **Advanced options** if a custom slug driver needs additional columns.
- **Relation clearing** (v2.6.0+): Eager-loaded relations added by third-party extensions are dropped from each model before processing, preventing them from accumulating across a chunk.
- **Chunked database queries**: Processes large datasets in chunks (75,000 rows by default). Each chunk is discarded before the next is fetched, keeping Eloquent model RAM bounded.
- **Automatic garbage collection**: Runs after each set is flushed to disk to reclaim any remaining cyclic references.

**Enable large chunk size (risky)**: For enterprise forums where generation speed is the primary concern. Increases chunk size from 75k to 150k rows. Doubles peak Eloquent RAM per chunk — only enable after verifying your server has sufficient headroom. Also activates column pruning if not already enabled.

### Search Engine Compliance

The extension ensures full search engine compliance:

- **Domain Consistency**: All sitemaps served from your main forum domain
- **Unified URLs**: Consistent structure (`/sitemap.xml`, `/sitemap-1.xml`) regardless of storage
- **Automatic Proxying**: External storage content proxied through your domain
- **Google Search Console Compatible**: Works seamlessly with all major search engines

### Scheduling

Cached sitemaps automatically update via the Flarum scheduler. Configure the frequency in extension settings.

Learn more about [Flarum scheduler setup](https://discuss.flarum.org/d/24118).

## Robots.txt Generation

The extension automatically generates a standards-compliant `robots.txt` file at `/robots.txt` that works seamlessly with your sitemap configuration. It replaces any existing robots.txt functionality from other extensions like `v17development/flarum-seo`.

### Features

- **Dynamic Path Detection**: Automatically detects admin, API, and forum paths from your Flarum configuration
- **Settings Integration**: Respects your sitemap exclusion settings (excludeUsers, excludeTags)
- **Extensible System**: Other extensions can easily add, remove, or modify robots.txt entries
- **Standards Compliant**: Generates proper robots.txt format with user-agent grouping
- **Automatic Sitemap References**: Includes your sitemap URL automatically

### Default Behavior

The generated robots.txt includes:

```
User-agent: *
Disallow: /admin
Disallow: /admin/
Disallow: /api
Disallow: /api/
Disallow: /settings
Disallow: /notifications
Disallow: /logout
Disallow: /reset
Disallow: /confirm

Sitemap: https://yourforum.com/sitemap.xml
```

**Conditional entries** (only included when relevant):
- **User profiles** (`/u/`) - Disallowed when `excludeUsers` setting is enabled
- **Tag pages** (`/t/` and `/tags`) - Disallowed when `excludeTags` setting is enabled and flarum/tags extension is installed

### Integration with Sitemap Settings

The robots.txt generation automatically respects your sitemap configuration:

- When **"Exclude users from sitemap"** is enabled, user profile pages (`/u/`) are disallowed
- When **"Exclude tags from sitemap"** is enabled, tag pages (`/t/`, `/tags`) are disallowed
- The sitemap URL is automatically included based on your forum's URL configuration

This ensures consistency between what's in your sitemap and what's allowed in robots.txt.

## Extending the Extension

This extension provides comprehensive APIs for customizing both XML sitemaps and robots.txt generation.

### Extending XML Sitemaps

#### Using the Unified Sitemap Extender (Recommended)

The recommended way to extend sitemaps uses the unified `Sitemap` extender with method chaining:

```php
use FoF\Sitemap\Extend;

return [
    (new Extend\Sitemap())
        ->addResource(YourCustomResource::class)
        ->removeResource(\FoF\Sitemap\Resources\Tag::class)
        ->replaceResource(\FoF\Sitemap\Resources\User::class, YourCustomUserResource::class)
        ->addStaticUrl('reviews.index')
        ->forceCached(),
];
```

**Available Methods:**
- **`addResource(string $resourceClass)`**: Add a custom resource to the sitemap
- **`removeResource(string $resourceClass)`**: Remove an existing resource from the sitemap
- **`replaceResource(string $oldResourceClass, string $newResourceClass)`**: Replace an existing resource
- **`addStaticUrl(string $routeName)`**: Add a static URL by route name
- **`forceCached()`**: Force cached mode for managed hosting environments

#### Creating Custom Resources

Create a class that extends `FoF\Sitemap\Resources\Resource`:

```php
use FoF\Sitemap\Resources\Resource;
use FoF\Sitemap\Sitemap\Frequency;

class YourCustomResource extends Resource
{
    public function query(): Builder
    {
        return YourModel::query()->where('is_public', true);
    }

    public function url($model): string
    {
        return $this->generateRouteUrl('your.route', ['id' => $model->id]);
    }

    public function priority(): float
    {
        return 0.7;
    }

    public function frequency(): string
    {
        return Frequency::WEEKLY;
    }

    public function lastModifiedAt($model): Carbon
    {
        return $model->updated_at ?? $model->created_at;
    }
    
    // Optional: Dynamic values based on model data
    public function dynamicFrequency($model): ?string
    {
        $daysSinceActivity = $model->updated_at->diffInDays(now());
        
        if ($daysSinceActivity < 1) return Frequency::HOURLY;
        if ($daysSinceActivity < 7) return Frequency::DAILY;
        return Frequency::WEEKLY;
    }
}
```

### Extending Robots.txt

#### Using the Robots Extender

Extensions can customize robots.txt using the `Robots` extender:

```php
use FoF\Sitemap\Extend\Robots;

return [
    (new Robots())
        ->addEntry(MyCustomRobotsEntry::class)
        ->removeEntry(\FoF\Sitemap\Robots\Entries\ApiEntry::class)
        ->replace(\FoF\Sitemap\Robots\Entries\AdminEntry::class, MyCustomAdminEntry::class),
];
```

**Available Methods:**
- **`addEntry(string $entryClass)`**: Add a custom robots.txt entry
- **`removeEntry(string $entryClass)`**: Remove an existing entry
- **`replace(string $oldEntryClass, string $newEntryClass)`**: Replace an existing entry

#### Creating Custom Robots Entries

Create a class that extends `FoF\Sitemap\Robots\RobotsEntry`:

```php
use FoF\Sitemap\Robots\RobotsEntry;

class MyCustomRobotsEntry extends RobotsEntry
{
    public function getRules(): array
    {
        return [
            // Use helper methods for clean, readable code
            $this->disallowForAll('/private'),
            $this->crawlDelayFor('Googlebot', 10),
            $this->allowFor('Googlebot', '/special-for-google'),
            $this->disallowFor('BadBot', '/'),
            $this->sitemap('https://example.com/news-sitemap.xml'),
        ];
    }
    
    public function enabled(): bool
    {
        return static::$settings->get('my-extension.enable-robots', true);
    }
}
```

**Helper Methods Available:**
- `disallowForAll(string $path)`, `disallowFor(string $userAgent, string $path)`
- `allowForAll(string $path)`, `allowFor(string $userAgent, string $path)`
- `crawlDelayForAll(int $seconds)`, `crawlDelayFor(string $userAgent, int $seconds)`
- `sitemap(string $url)`

#### Extending Default Entries

All default entries can be extended to modify their behavior:

```php
class CustomAdminEntry extends \FoF\Sitemap\Robots\Entries\AdminEntry
{
    protected function buildAdminRules(string $adminPath): array
    {
        return [
            $this->disallowForAll($adminPath),
            $this->disallowForAll(rtrim($adminPath, '/') . '/'),
            // Allow Googlebot to access public admin stats
            $this->allowFor('Googlebot', $adminPath . '/public-stats'),
        ];
    }
}
```

### Legacy Extenders (Deprecated)

The following extenders are deprecated and will be removed in Flarum 2.0:

```php
// Deprecated - use unified Sitemap extender instead
new \FoF\Sitemap\Extend\RegisterResource(YourResource::class);
new \FoF\Sitemap\Extend\RemoveResource(\FoF\Sitemap\Resources\Tag::class);
new \FoF\Sitemap\Extend\RegisterStaticUrl('reviews.index');
new \FoF\Sitemap\Extend\ForceCached();
```

## Configuration Options

### Sitemap Elements

Control which elements are included in your XML sitemaps:

- **Include priority values**: Used by some search engines like Bing and Yandex (ignored by Google)
- **Include change frequency values**: Helps search engines schedule crawling (ignored by Google)

Both are enabled by default. When enabled, the extension uses intelligent frequency calculation based on actual content activity.

### Performance Settings

- **Enable column pruning** (default: on): Fetches only the columns needed to generate sitemap URLs. Safe for most setups; disable only if a custom slug driver or visibility scope requires additional columns.
- **Enable large chunk size (risky)**: Increases the database fetch chunk size from 75k to 150k rows. Only enable if you have verified sufficient server memory, as it doubles the peak Eloquent RAM per chunk.

## Server Configuration

### Queue Workers and Multi-Server Deployments

When using cached multi-file mode, sitemap files are written to the `public/sitemaps/` directory by queue workers. Ensure your queue workers can write to this location.

#### Docker and Containerized Environments

**Problem**: Worker containers must have write access to the same `public/` directory as your web server.

**Solution**: Mount the `public/` directory in your queue worker container:

```yaml
services:
  web:
    volumes:
      - ./public:/var/www/public:delegated
      # ... other volumes

  worker:
    volumes:
      - ./public:/var/www/public:delegated  # Required for sitemap generation
      # ... other volumes
```

#### Supervisor/Systemd Workers

**Problem**: Queue workers running as system services must have proper file permissions.

**Solution**: Ensure the worker process runs as a user with write access to `public/sitemaps/`:

```bash
# Check worker user permissions
sudo -u queue-worker-user touch /path/to/flarum/public/sitemaps/test.xml

# If permission denied, fix ownership:
sudo chown -R queue-worker-user:www-data /path/to/flarum/public/sitemaps
sudo chmod 775 /path/to/flarum/public/sitemaps
```

#### Multi-Server Deployments

**Problem**: If your web servers and queue workers are on separate machines, workers cannot write to local disk.

**Solutions**:
1. **Use shared storage**: Mount a shared NFS/GlusterFS volume on both web servers and workers
2. **Use remote storage** (recommended): Configure S3/CDN storage, eliminating local disk dependency

After configuring remote storage, sitemaps will be stored remotely but still served from your main domain (proxied automatically by the extension).

### Nginx Configuration

If accessing `/sitemap.xml`, `/sitemap-X.xml` or `/robots.txt` results in nginx 404 errors, add these rules:

```nginx
# FoF Sitemap & Robots — Flarum handles everything
location = /sitemap.xml {
    rewrite ^ /index.php?$query_string last;
    add_header Cache-Control "max-age=0";
}

location ^~ /sitemap- {
    rewrite ^ /index.php?$query_string last;
    add_header Cache-Control "max-age=0";
}

location = /robots.txt {
    rewrite ^ /index.php?$query_string last;
    add_header Cache-Control "max-age=0";
}
```

## Troubleshooting

### Memory Issues

Since v2.6.0, sitemap generation streams XML directly to storage rather than holding full XML strings in PHP RAM. Peak memory is dominated by the Eloquent model chunk size, not XML serialisation. If you still encounter OOM errors:

1. **Verify column pruning is enabled**: Check **Advanced options → Enable column pruning** in the admin panel. This is on by default but may have been disabled. It provides a ~7× per-model RAM reduction for Discussion and User queries.

2. **Use cached multi-file mode**: Switch from runtime to cached mode in extension settings so generation runs as a background job rather than on a web request.

3. **Check PHP memory limit**:
   ```bash
   php -i | grep memory_limit
   ```
   256MB is sufficient for most large forums with column pruning enabled. If you have many extensions that add columns or relations to User/Discussion models, 512MB provides a safe margin.

4. **Increase memory limit** if needed:
   ```ini
   memory_limit = 512M
   ```

5. **Monitor during generation**:
   ```bash
   php flarum fof:sitemap:build --verbose
   ```

### Regenerating Sitemaps

If you've updated the extension or changed storage settings:

```bash
php flarum fof:sitemap:build
```

### Debug Logging

When Flarum is in debug mode, the extension provides detailed logging for:
- Sitemap generation and serving
- Memory usage during generation
- Content proxying from external storage
- Route parameter extraction
- Request handling issues

Check your Flarum logs (`storage/logs/`) for detailed information.

### Performance Benchmarks

Typical generation times and peak memory usage (v2.6.0+, column pruning enabled, cached multi-file mode):

| Forum Size | Total items | Peak Memory |
|------------|-------------|-------------|
| Small | <10k | <50MB |
| Medium | ~100k | ~80MB |
| Large | ~500k | ~150MB |
| Production replica | ~784k (702k users + 81k discussions) | ~296MB |
| Enterprise | 1M+ | ~350MB |

*Measured on standard hardware. Peak memory is dominated by the Eloquent chunk size (75k rows × model footprint). Extensions that add columns or relations to User/Discussion models will increase per-model footprint.*

## Technical Details

### XML Generation

The extension uses PHP's `XMLWriter` for optimal performance and security:

- **Automatic escaping**: All content is properly escaped for XML safety
- **Memory efficient**: Streams XML generation without holding entire documents in memory
- **Standards compliant**: Generates valid XML sitemaps per sitemaps.org protocol
- **Type safe**: Uses strict typing throughout for reliability

### Database Optimization

Sitemap generation is optimized for minimal database impact:

- **Chunked iteration**: Uses Laravel's `each()` method with configurable chunk sizes
- **Query caching**: Eliminates duplicate queries per resource type
- **Visibility scoping**: Respects Flarum's visibility system (guest user perspective)
- **Index optimization**: Relies on proper database indexes for `created_at`, `updated_at`, `is_hidden`, etc.

### Architecture

The extension follows modern PHP practices:

- **PHP 8.0+ features**: Uses constructor property promotion, null coalescing, and strict types
- **Dependency injection**: Leverages Flarum's service container
- **Event-driven**: Integrates with Flarum's event system for cache invalidation
- **Extensible design**: Provides extenders for third-party customization
- **Resource pattern**: Clean abstraction for sitemap content types

## Changelog

### v2.6.0

- **Streaming XML generation**: `UrlSet` now writes directly to a `php://temp` stream flushed every 500 entries. `DeployInterface::storeSet()` receives a stream resource rather than a string — Disk and ProxyDisk backends pass it straight to Flysystem with zero string copy. Eliminates the primary source of OOM errors on large forums. See [BREAKING-CHANGES.md](BREAKING-CHANGES.md) for migration details.
- **Column pruning** (default on): Fetches only the columns needed for URL/date generation for Discussion and User resources, reducing per-model RAM by ~7×.
- **Relation clearing**: Drops eager-loaded relations from each model before processing, preventing third-party `$with` additions from accumulating RAM across a chunk.
- **Split performance settings**: "Risky performance improvements" now controls chunk size only. Column pruning has its own independent toggle in Advanced options.

## Acknowledgments

The initial version of this extension was sponsored by [profesionalreview.com](https://www.profesionalreview.com/).

## Links

- [![OpenCollective](https://img.shields.io/badge/donate-friendsofflarum-44AEE5?style=for-the-badge&logo=open-collective)](https://opencollective.com/fof/donate)
- [Flarum Discuss post](https://discuss.flarum.org/d/14941)
- [Source code on GitHub](https://github.com/FriendsOfFlarum/sitemap)
- [Report an issue](https://github.com/FriendsOFflarum/sitemap/issues)
- [Download via Packagist](https://packagist.org/packages/fof/sitemap)
