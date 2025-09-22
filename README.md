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

This extension requires PHP 8.0 or greater.

Install manually with composer:

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

**Manual rebuild**:
```bash
php flarum fof:sitemap:build
```

#### Performance Optimizations

For enterprise customers with millions of items, the "Enable risky performance improvements" option reduces database response size by limiting returned columns. Only enable if generation takes over an hour or saturates your database connection.

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

- **Risky Performance Improvements**: For enterprise customers with millions of items. Reduces database response size but may break custom visibility scopes or slug drivers.

## Server Configuration

### Nginx Configuration

If accessing `/sitemap.xml` or `/robots.txt` results in nginx 404 errors, add these rules:

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

### Regenerating Sitemaps

If you've updated the extension or changed storage settings:

```bash
php flarum fof:sitemap:build
```

### Debug Logging

When Flarum is in debug mode, the extension provides detailed logging for:
- Sitemap generation and serving
- Content proxying from external storage
- Route parameter extraction
- Request handling issues

Check your Flarum logs (`storage/logs/`) for detailed information.

## Acknowledgments

The initial version of this extension was sponsored by [profesionalreview.com](https://www.profesionalreview.com/).

## Links

- [![OpenCollective](https://img.shields.io/badge/donate-friendsofflarum-44AEE5?style=for-the-badge&logo=open-collective)](https://opencollective.com/fof/donate)
- [Flarum Discuss post](https://discuss.flarum.org/d/14941)
- [Source code on GitHub](https://github.com/FriendsOfFlarum/sitemap)
- [Report an issue](https://github.com/FriendsOFflarum/sitemap/issues)
- [Download via Packagist](https://packagist.org/packages/fof/sitemap)
